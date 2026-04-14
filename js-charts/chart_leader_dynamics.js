/* global BacsUtils, Chart */
window.initializeLeaderDynamicsChart = () => {
  const canvasId = "leader-dynamics-chart";
  const prefix = "leader-dynamics";
  const controlsContainer = document.getElementById(`${prefix}-controls`);

  const layout = BacsUtils.createChartLayout(
      canvasId, prefix, "bi-rocket-takeoff", "Гонка еще не началась",
      "График динамики лидеров появится здесь автоматически, как только участники начнут отправлять решения."
  );
  if (!layout) {
 return;
}

  const submissions = window.BACS_PAGE_DATA.submissions || [];
  const students = window.BACS_PAGE_DATA.students || [];
  const contestData = window.BACS_PAGE_DATA.contest;

  const hasPoints = submissions.some(s => parseInt(s.points, 10) > 0);
  if (!hasPoints) {
      layout.flexContainer.style.display = 'none';
      if (controlsContainer) {
 controlsContainer.style.display = 'none';
}
      layout.emptyStateContainer.style.display = 'flex';
      return;
  } else {
      layout.flexContainer.style.display = 'flex';
      if (controlsContainer) {
 controlsContainer.style.display = '';
}
      layout.emptyStateContainer.style.display = 'none';
  }

  let currentMode = "realtime";
  let precomputedData = null;
  const MAX_PARTICIPANTS_TO_SHOW = 31;
  const NORMALIZED_AGGREGATION_STEP = 5;

  const raceComparator = (a, b) => (b.points !== a.points) ? (b.points - a.points) : (a.lastImprovement - b.lastImprovement);

  const sortedSubmissions = submissions.slice().sort((a, b) => a.submit_time - b.submit_time);
  const warper = BacsUtils.createTimeWarper(sortedSubmissions, contestData.starttime);

  const precomputeAllData = () => {
    let allUserStates = {};
    students.forEach((student) => {
 allUserStates[student.id] = {id: student.id, name: `${student.firstname} ${student.lastname}`, points: 0, lastImprovement: 0};
});

    const allEvents = [];
    const eventsByUser = {};
    students.forEach((s) => (eventsByUser[s.id] = []));

    const userTaskScores = {};
    sortedSubmissions.forEach((sub) => {
      if (!allUserStates[sub.user_id]) {
 return;
}
      const newPoints = parseInt(sub.points, 10) || 0;
      const userScores = (userTaskScores[sub.user_id] = userTaskScores[sub.user_id] || {});
      const oldTaskScore = userScores[sub.task_id] || 0;
      if (newPoints > oldTaskScore) {
        userScores[sub.task_id] = newPoints;
        const timeElapsedMs = Math.max(0, (sub.submit_time - contestData.starttime) * 1000);
        const event = {time: timeElapsedMs, userId: sub.user_id, delta: newPoints - oldTaskScore};
        allEvents.push(event);
        eventsByUser[sub.user_id].push(event);
      }
    });

    let maxContestScore = 0,
globalMaxSubmitTime = 0;
    let simulationStates = JSON.parse(JSON.stringify(allUserStates));
    const rankSnapshots = [{time: 0, ranks: {}}];

    let rankedUsers = Object.values(simulationStates).sort(raceComparator);
    rankedUsers.forEach((user, index) => {
 if (user) {
 rankSnapshots[0].ranks[user.id] = index + 1;
}
});

    for (const event of allEvents) {
      if (simulationStates[event.userId]) {
          simulationStates[event.userId].points += event.delta;
          simulationStates[event.userId].lastImprovement = event.time;
          maxContestScore = Math.max(maxContestScore, simulationStates[event.userId].points);
          globalMaxSubmitTime = Math.max(globalMaxSubmitTime, event.time);
      }
      rankedUsers = Object.values(simulationStates).sort(raceComparator);
      const snapshot = {time: event.time, ranks: {}};
      rankedUsers.forEach((user, index) => {
 if (user) {
 snapshot.ranks[user.id] = index + 1;
}
});
      rankSnapshots.push(snapshot);
    }
    const finalRankedUsers = Object.values(simulationStates).sort(raceComparator);
    return {finalRankedUsers, rankSnapshots, eventsByUser, maxContestScore, globalMaxSubmitTime};
  };

  const clickHandler = (chart, dsIndex) => {
      BacsUtils.toggleDatasetFocus(chart, dsIndex, `custom-${prefix}-legend`, p => p.isDummy ? 0 : (p.isLast ? 6 : 4));
  };

  const generateChartConfig = (mode) => {
    if (!precomputedData) {
 precomputedData = precomputeAllData();
}
    const {finalRankedUsers, rankSnapshots, eventsByUser, maxContestScore, globalMaxSubmitTime} = precomputedData;
    const topUsers = finalRankedUsers.slice(0, MAX_PARTICIPANTS_TO_SHOW);

    const trueFinalRanks = {};
    finalRankedUsers.forEach((user, index) => {
 trueFinalRanks[user.id] = index + 1;
});

    const finalRealTimeMs = globalMaxSubmitTime > 0 ? globalMaxSubmitTime : (Date.now() - contestData.starttime * 1000);
    const chartMaxXVisual = mode === "normalized" ? 100 : warper.r2v(finalRealTimeMs) * 1.05;
    const curveOffset = mode === "normalized" ? 1.5 : Math.max(60000, chartMaxXVisual * 0.015);

    const datasets = [];
    const datasetsInfo = [];
    layout.legendContainer.innerHTML = `<h6 class="text-muted mb-3" style="font-size:0.85rem; 
    font-weight:600; text-transform:uppercase;">Top Participants</h6>`;

    if (mode === "normalized") {
      const normalizedSnapshots = [];
      for (let p = 0; p <= 100; p += NORMALIZED_AGGREGATION_STEP) {
        const targetPoints = (p / 100) * maxContestScore;
        const progressTimestamps = topUsers.map((user) => {
          let timeAtProgress = Infinity;
          let cumulativePoints = 0;
          for (const event of eventsByUser[user.id]) {
            cumulativePoints += event.delta;
            if (cumulativePoints >= targetPoints) {
 timeAtProgress = event.time; break;
}
          }
          return {userId: user.id, time: timeAtProgress};
        });
        progressTimestamps.sort((a, b) => a.time - b.time);
        const snapshot = {progress: p, ranks: {}, times: {}};
        progressTimestamps.forEach((data, index) => {
 snapshot.ranks[data.userId] = index + 1; snapshot.times[data.userId] = data.time;
});
        normalizedSnapshots.push(snapshot);
      }

      topUsers.forEach((user) => {
        const rawData = [];
        const maxUserProgress = maxContestScore > 0 ? (user.points / maxContestScore) * 100 : 0;
        normalizedSnapshots.forEach(snap => {
            if (snap.progress <= maxUserProgress) {
 rawData.push({x: snap.progress, y: snap.ranks[user.id], realTime: snap.times[user.id]});
}
        });

        let smoothData = [];
        for (let i = 0; i < rawData.length; i++) {
            let pt = rawData[i];
            if (i > 0 && (pt.x - rawData[i - 1].x) > curveOffset * 1.2 && rawData[i - 1].y !== pt.y) {
                smoothData.push({x: pt.x - curveOffset, y: rawData[i - 1].y, realTime: pt.realTime, isDummy: true});
            }
            smoothData.push({...pt});
        }
        if (smoothData.length > 0) {
            smoothData[smoothData.length - 1].isLast = true;
            datasetsInfo.push({user, smoothData, trueRank: trueFinalRanks[user.id]});
        }
      });
    } else {
      topUsers.forEach((user) => {
        const rawData = rankSnapshots.map((snap) => ({x: warper.r2v(snap.time),
          y: snap.ranks[user.id] || (students.length + 1), realTime: snap.time}));
        let filteredData = rawData.filter(p => p.realTime <= user.lastImprovement);

        if (filteredData.length > 0 && filteredData[filteredData.length - 1].realTime < user.lastImprovement) {
            filteredData.push({x: warper.r2v(user.lastImprovement),
              y: filteredData[filteredData.length - 1].y, realTime: user.lastImprovement});
        }
        if (filteredData.length > 0) {
            filteredData.push({x: chartMaxXVisual, y: filteredData[filteredData.length - 1].y,
              realTime: warper.v2r(chartMaxXVisual), isDummy: true});
        }

        let smoothData = [];
        if (filteredData.length > 0) {
            smoothData.push({...filteredData[0]});
            let currentY = filteredData[0].y;
            for (let i = 1; i < filteredData.length; i++) {
                let pt = filteredData[i];
                if (pt.y !== currentY) {
                    if ((pt.x - smoothData[smoothData.length - 1].x) > curveOffset * 1.2) {
                        smoothData.push({x: pt.x - curveOffset,
                          y: currentY, realTime: warper.v2r(pt.x - curveOffset), isDummy: true});
                    }
                    smoothData.push({...pt});
                    currentY = pt.y;
                } else if (i === filteredData.length - 1) {
                    smoothData.push({...pt});
                }
            }
        }
        if (smoothData.length > 0) {
            smoothData[smoothData.length - 1].isLast = true;
            datasetsInfo.push({user, smoothData, trueRank: trueFinalRanks[user.id]});
        }
      });
    }

    datasetsInfo.sort((a, b) => a.trueRank - b.trueRank);
    datasetsInfo.forEach((info, index) => {
        const baseHex = BacsUtils.COLORS[index % BacsUtils.COLORS.length];
        const colorNormal = baseHex + "E6";
        const dsIndex = datasets.length;

        datasets.push({
          label: info.user.name,
          data: info.smoothData,
          baseColor: baseHex,
          borderColor: colorNormal,
          backgroundColor: colorNormal,
          fill: false,
          stepped: false,
          tension: 0.4,
          cubicInterpolationMode: 'monotone',
          borderWidth: 2,
          clip: false,
          pointRadius: info.smoothData.map(p => p.isLast ? 4 : 0),
          pointBackgroundColor: info.smoothData.map(() => "#FFFFFF"),
          pointBorderColor: info.smoothData.map(() => colorNormal),
          pointBorderWidth: info.smoothData.map(() => 2),
          pointHoverRadius: 6,
          pointHoverBackgroundColor: colorNormal,
          pointHoverBorderColor: "#FFFFFF",
          pointHoverBorderWidth: 2,
        });

        const rankText = info.trueRank > students.length ? "-" : info.trueRank;
        BacsUtils.createLegendItem(layout.legendContainer, baseHex, info.user.name, `#${rankText}`,
          () => clickHandler(window.leaderDynamicsChartInstance, dsIndex));
    });

    const TEXT_COLOR = "#6b7280";
    const xAxisTitle = mode === "normalized" ? "Progress to Contest Max Score (%)" : "Time from start (Smart Compressed)";

    const tooltipConfig = BacsUtils.getTooltipBaseConfig({
        title: (c) => (c && c[0] && c[0].dataset) ? c[0].dataset.label : "",
        label: (c) => {
            if (!c || !c.raw || c.raw.isDummy) {
 return "";
}
            if (currentMode === "normalized") {
              let timeStr = (c.raw.realTime && c.raw.realTime !== Infinity) ? BacsUtils.formatTime(c.raw.realTime / 1000) : "N/A";
              return `Rank: #${c.parsed.y} at ${c.parsed.x.toFixed(0)}% (Time: ${timeStr})`;
            }
            return `Rank: #${c.parsed.y}  •  ${BacsUtils.formatTime(c.raw.realTime / 1000)}`;
        }
    });

    return {
      type: "line",
      data: {datasets},
      plugins: [BacsUtils.getLineClickPlugin(clickHandler)],
      options: {
        responsive: true,
        maintainAspectRatio: false,
        clip: false,
        layout: {padding: {top: 25, right: 30, left: 20, bottom: 20}},
        scales: {
          x: {
            type: "linear",
            title: {display: true, text: xAxisTitle, color: TEXT_COLOR, font: {weight: '500'}},
            border: {display: false},
            ticks: {
                color: TEXT_COLOR,
                maxRotation: 0,
                callback: (value) => mode === "normalized" ? (value % 10 === 0 && value >= 0 && value <= 100 ? `${value}%` : "")
                : (value >= 0 ? BacsUtils.formatTime(warper.v2r(value) / 1000) : "")
            },
            grid: {color: "rgba(0,0,0,0)", drawBorder: false},
            min: 0,
            max: chartMaxXVisual,
          },
          y: {
            title: {display: true, text: contestData.localizedStrings.rank || "Rank", color: TEXT_COLOR, font: {weight: '500'}},
            reverse: true,
            min: 1,
            max: topUsers.length > 0 ? topUsers.length + 1 : MAX_PARTICIPANTS_TO_SHOW + 1,
            border: {display: false},
            ticks: {color: TEXT_COLOR, stepSize: 1, precision: 0, padding: 10},
            grid: {color: "rgba(0, 0, 0, 0.04)", drawBorder: false},
          },
        },
        animation: {duration: 1000, easing: "easeOutQuart"},
        plugins: {
            legend: {display: false},
            tooltip: tooltipConfig,
            zoom: {zoom: {wheel: {enabled: false}, pinch: {enabled: false}, drag: {enabled: false}}, pan: {enabled: false}}
        },
      },
    };
  };

  const drawChart = () => {
    const config = generateChartConfig(currentMode);
    if (!config) {
 return;
}
    if (window.leaderDynamicsChartInstance) {
 window.leaderDynamicsChartInstance.destroy();
}
    window.leaderDynamicsChartInstance = new Chart(document.getElementById(canvasId).getContext('2d'), config);
  };

  if (controlsContainer) {
    controlsContainer.addEventListener("click", (e) => {
      const button = e.target.closest("button");
      if (!button || button.classList.contains("active")) {
 return;
}
      const newMode = button.dataset.mode;
      if (newMode && newMode !== currentMode) {
        currentMode = newMode;
        controlsContainer.querySelectorAll("button").forEach((btn) => {
          const isActive = btn.dataset.mode === newMode;
          btn.classList.toggle("active", isActive);
          btn.classList.toggle("btn-primary", isActive);
          btn.classList.toggle("btn-outline-secondary", !isActive);
        });
        drawChart();
      }
    });
  }

  drawChart();
};