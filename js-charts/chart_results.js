/* global BacsUtils, Chart */
window.renderResultsGraph = () => {
  if (window.resultsChartInstance) {
 return;
}
  const canvasId = 'results-graph-chart';
  const prefix = 'results-graph';

  const layout = BacsUtils.createChartLayout(
    canvasId,
    prefix,
    'bi-graph-up',
    'График результатов пока пуст',
    'Здесь появится индивидуальная динамика набора баллов. Отправьте решение, чтобы дать старт графику!',
  );
  if (!layout) {
 return;
}

  const submissions = window.BACS_PAGE_DATA.submissions || [];
  const students = window.BACS_PAGE_DATA.students || [];
  const contestData = window.BACS_PAGE_DATA.contest;

  const hasPoints = submissions.some((s) => parseInt(s.points, 10) > 0);
  if (!hasPoints) {
    layout.flexContainer.style.display = 'none';
    layout.emptyStateContainer.style.display = 'flex';
    return;
  } else {
    layout.flexContainer.style.display = 'flex';
    layout.emptyStateContainer.style.display = 'none';
  }

  const taskMap = {},
    taskFullMap = {};
  (window.BACS_PAGE_DATA.tasks || []).forEach((t) => {
    taskMap[t.task_id] = t.task_order;
    taskFullMap[t.task_id] = t.task_order + '. ' + t.name;
  });

  const sortedSubmissions = submissions.slice().sort((a, b) => a.submit_time - b.submit_time);
  const warper = BacsUtils.createTimeWarper(sortedSubmissions, contestData.starttime);

  const userData = {};
  students.forEach((student) => {
    userData[student.id] = {
      name: `${student.firstname} ${student.lastname}`,
      data: [{x: 0, y: 0, taskName: 'Start', taskShort: '', delta: 0, realTime: 0}],
      totalScore: 0,
      taskScores: {},
    };
  });

  let maxContestScore = 0;
  let maxRealSubmitTimeMs = 0;

  const tempUserScores = {};
  sortedSubmissions.forEach((sub) => {
    const points = parseInt(sub.points, 10) || 0;
    if (!tempUserScores[sub.user_id]) {
 tempUserScores[sub.user_id] = {};
}
    if (points > (tempUserScores[sub.user_id][sub.task_id] || 0)) {
 tempUserScores[sub.user_id][sub.task_id] = points;
}
  });
  Object.values(tempUserScores).forEach((tasks) => {
    const total = Object.values(tasks).reduce((a, b) => a + b, 0);
    if (total > maxContestScore) {
 maxContestScore = total;
}
  });

  sortedSubmissions.forEach((sub) => {
    const userId = sub.user_id;
    if (!userData[userId]) {
 return;
}
    const points = sub.points === null ? 0 : parseInt(sub.points, 10);
    const user = userData[userId];
    const oldTaskScore = user.taskScores[sub.task_id] || 0;

    if (points > oldTaskScore) {
      const delta = points - oldTaskScore;
      user.taskScores[sub.task_id] = points;
      user.totalScore += delta;

      const realTimeElapsed = Math.max(0, (sub.submit_time - contestData.starttime) * 1000);
      if (realTimeElapsed > maxRealSubmitTimeMs) {
 maxRealSubmitTimeMs = realTimeElapsed;
}

      user.data.push({
        x: warper.r2v(realTimeElapsed),
        y: user.totalScore,
        taskName: taskFullMap[sub.task_id] || `Task ${sub.task_id}`,
        taskShort: taskMap[sub.task_id] || '?',
        delta: delta,
        realTime: realTimeElapsed,
      });
    }
  });

  const finalRealTimeMs = maxRealSubmitTimeMs > 0 ? maxRealSubmitTimeMs : Date.now() - contestData.starttime * 1000;
  const chartMaxXVisual = warper.r2v(finalRealTimeMs) * 1.05;

  Object.values(userData).forEach((user) => {
    const lastPoint = user.data[user.data.length - 1];
    if (user.totalScore >= maxContestScore && maxContestScore > 0) {
 return;
}
    if (lastPoint.x < chartMaxXVisual) {
      user.data.push({
        x: chartMaxXVisual,
        y: lastPoint.y,
        taskName: null,
        delta: 0,
        realTime: warper.v2r(chartMaxXVisual),
      });
    }
  });

  const curveOffset = Math.max(60000, chartMaxXVisual * 0.015);
  Object.values(userData).forEach((user) => {
    let smoothData = [];
    for (let i = 0; i < user.data.length; i++) {
      let pt = user.data[i];
      if (i > 0 && pt.x - user.data[i - 1].x > curveOffset * 1.5 && user.data[i - 1].y !== pt.y) {
        smoothData.push({
          x: pt.x - curveOffset,
          y: user.data[i - 1].y,
          taskName: null,
          delta: 0,
          isDummy: true,
          realTime: warper.v2r(pt.x - curveOffset),
        });
      }
      smoothData.push(pt);
    }
    smoothData[smoothData.length - 1].isLast = true;
    user.data = smoothData;
  });

  const datasets = [];
  let colorIndex = 0;
  const sortedUsers = Object.values(userData).sort((a, b) => b.totalScore - a.totalScore);
  layout.legendContainer.innerHTML =
    '<h6 class="text-muted mb-3" style="font-size:0.85rem; font-weight:600; text-transform:uppercase;">Participants</h6>';

  const clickHandler = (chart, dsIndex) => {
    BacsUtils.toggleDatasetFocus(chart, dsIndex, `custom-${prefix}-legend`, (p) =>
      !p.isDummy && (p.delta > 0 || p.isLast) ? 5 : 0,
    );
  };

  sortedUsers.forEach((user) => {
    if (user.totalScore > 0) {
      const baseHex = BacsUtils.COLORS[colorIndex % BacsUtils.COLORS.length];
      const colorNormal = baseHex + 'E6';
      const dsIndex = datasets.length;

      datasets.push({
        label: user.name,
        data: user.data,
        baseColor: baseHex,
        borderColor: colorNormal,
        backgroundColor: colorNormal,
        fill: false,
        stepped: false,
        tension: 0.4,
        cubicInterpolationMode: 'monotone',
        borderWidth: 2,
        pointRadius: user.data.map((p) => (p.isLast ? 4 : 0)),
        pointBackgroundColor: user.data.map(() => '#FFFFFF'),
        pointBorderColor: user.data.map(() => colorNormal),
        pointBorderWidth: user.data.map(() => 2),
        pointHoverRadius: 6,
        pointHoverBackgroundColor: colorNormal,
        pointHoverBorderColor: '#FFFFFF',
        pointHoverBorderWidth: 2,
      });

      BacsUtils.createLegendItem(layout.legendContainer, baseHex, user.name, user.totalScore, () =>
        clickHandler(window.resultsChartInstance, dsIndex),
      );
      colorIndex++;
    }
  });

  const TEXT_COLOR = '#6b7280';
  const GRID_COLOR = 'rgba(0, 0, 0, 0.04)';

  const activeLabelsPlugin = {
    id: 'activeLabels',
    afterDatasetsDraw(chart) {
      const ctx = chart.ctx;
      chart.data.datasets.forEach((dataset, i) => {
        const meta = chart.getDatasetMeta(i);
        if (dataset.borderWidth === 4 && !meta.hidden) {
          ctx.font = "600 11px 'Inter', sans-serif";
          ctx.textAlign = 'left';
          ctx.textBaseline = 'middle';
          meta.data.forEach((element, index) => {
            const dataPoint = dataset.data[index];
            if (dataPoint && !dataPoint.isDummy && dataPoint.delta > 0 && dataPoint.taskName) {
              const text = dataPoint.taskName;
              const x = element.x + 10,
                y = element.y - 12;
              const textWidth = ctx.measureText(text).width;
              ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
              ctx.beginPath();
              ctx.roundRect(x - 4, y - 8, textWidth + 8, 16, 4);
              ctx.fill();
              ctx.fillStyle = dataset.baseColor;
              ctx.fillText(text, x, y);
            }
          });
        }
      });
    },
  };

  const tooltipConfig = BacsUtils.getTooltipBaseConfig({
    title: (c) =>
      c && c[0] && c[0].dataset && c[0].raw
        ? c[0].raw.taskName
          ? `${c[0].dataset.label} - ${c[0].raw.taskName}`
          : c[0].dataset.label
        : '',
    label: (c) => {
      if (!c || !c.raw || c.raw.isDummy) {
 return '';
}
      let label = `Score: ${c.parsed.y}`;
      if (c.raw.delta > 0) {
 label += ` (+${c.raw.delta})`;
}
      label += `  •  ${BacsUtils.formatTime(c.raw.realTime / 1000)}`;
      return label;
    },
  });

  window.resultsChartInstance = new Chart(document.getElementById(canvasId).getContext('2d'), {
    type: 'line',
    data: {datasets: datasets},
    plugins: [activeLabelsPlugin, BacsUtils.getLineClickPlugin(clickHandler)],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {padding: {top: 30, right: 20, left: 10, bottom: 10}},
      scales: {
        x: {
          type: 'linear',
          title: {
            display: true,
            text: 'Time from start (Smart Compressed)',
            color: TEXT_COLOR,
            font: {weight: '500'},
          },
          border: {display: false},
          ticks: {
            color: TEXT_COLOR,
            maxRotation: 0,
            callback: (value) => BacsUtils.formatTime(warper.v2r(value) / 1000),
          },
          grid: {color: 'rgba(0,0,0,0)', drawBorder: false},
          max: chartMaxXVisual,
        },
        y: {
          beginAtZero: true,
          border: {display: false},
          title: {
            display: true,
            text: contestData.localizedStrings.points || 'Score',
            color: TEXT_COLOR,
            font: {weight: '500'},
          },
          ticks: {color: TEXT_COLOR, padding: 10},
          grid: {color: GRID_COLOR, drawBorder: false},
        },
      },
      animation: {duration: 1000, easing: 'easeOutQuart'},
      plugins: {
        legend: {display: false},
        tooltip: tooltipConfig,
        zoom: {
          zoom: {wheel: {enabled: false}, pinch: {enabled: false}, drag: {enabled: false}},
          pan: {enabled: false},
        },
      },
    },
  });
};
