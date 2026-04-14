/* global BacsUtils, Chart */
window.renderTaskDynamicsGraph = () => {
  const select = document.getElementById('task-dynamics-select');
  const canvas = document.getElementById('task-dynamics-chart');
  const placeholder = document.getElementById('task-dynamics-placeholder');
  const statsContainer = document.getElementById('task-stats-info');
  const detailsContainer = document.getElementById('task-click-details');
  const detailsTableBody = document.querySelector('#task-details-table tbody');
  const detailsTitle = document.getElementById('task-details-title');

  if (!select || !canvas || !placeholder) {
    return;
  }

  if (canvas.parentElement) {
    canvas.parentElement.style.minHeight = '600px';
    canvas.parentElement.style.position = 'relative';
  }

  const submissions = window.BACS_PAGE_DATA.submissions || [];
  const contestData = window.BACS_PAGE_DATA.contest;
  const students = window.BACS_PAGE_DATA.students || [];
  const tasks = window.BACS_PAGE_DATA.tasks || [];

  if (select.options.length <= 2 && tasks.length > 0) {
    tasks.forEach((t) => {
      const option = document.createElement('option');
      option.value = t.task_id;
      option.textContent = `${t.task_order}. ${t.name}`;
      select.appendChild(option);
    });
  }

  const studentMap = {};
  students.forEach((s) => (studentMap[s.id] = `${s.firstname} ${s.lastname}`));

  const taskMap = {};
  tasks.forEach((t) => (taskMap[t.task_id] = t.task_order + '. ' + t.name));

  if (window.taskDynamicsChartInstance) {
    window.taskDynamicsChartInstance.destroy();
    window.taskDynamicsChartInstance = null;
  }

  if (detailsContainer) {
    detailsContainer.style.display = 'none';
  }
  if (statsContainer) {
    statsContainer.innerHTML = '';
  }

  const taskId = parseInt(select.value, 10);

  if (taskId === 0 || isNaN(taskId)) {
    canvas.classList.add('d-none');
    placeholder.classList.remove('d-none');
    const existingEmpty = document.getElementById('task-dynamics-empty-state');
    if (existingEmpty) {
      existingEmpty.style.display = 'none';
    }
    return;
  }

  const ONE_YEAR_SECONDS = 365 * 24 * 60 * 60;
  const cutoffTimestamp = contestData.starttime + ONE_YEAR_SECONDS;
  let relevantSubmissions = submissions.filter((sub) => sub.submit_time <= cutoffTimestamp);

  if (taskId !== -1) {
    relevantSubmissions = relevantSubmissions.filter((sub) => sub.task_id === taskId);
  }

  if (relevantSubmissions.length === 0) {
    canvas.classList.add('d-none');
    placeholder.classList.add('d-none');

    const isAllTasks = taskId === -1;
    BacsUtils.createEmptyState(
      'task-dynamics-empty-state',
      placeholder.parentNode,
      placeholder.nextSibling,
      'bi-inbox',
      isAllTasks ? 'В контесте пока нет посылок' : 'Задача пока не решена',
      isAllTasks
        ? 'График появится, как только участники начнут сдавать решения.'
        : `По задаче <b>«${taskMap[taskId]}»</b> еще никто не отправлял решений.`,
    ).style.display = 'flex';
    return;
  }

  canvas.classList.remove('d-none');
  placeholder.classList.add('d-none');
  const existingEmpty = document.getElementById('task-dynamics-empty-state');
  if (existingEmpty) {
    existingEmpty.style.display = 'none';
  }

  // --- 2. CALCULATE GENERAL STATS ---
  const totalSubmits = relevantSubmissions.length;
  const VERDICT_ACCEPTED = 13;
  const acceptedSubmits = relevantSubmissions.filter((s) => s.result_id === VERDICT_ACCEPTED).length;
  const successRate = totalSubmits > 0 ? ((acceptedSubmits / totalSubmits) * 100).toFixed(1) : 0;

  BacsUtils.renderStatsBadges(statsContainer, totalSubmits, acceptedSubmits, successRate);

  // --- 3. algorithm time stamps ---
  relevantSubmissions.sort((a, b) => a.submit_time - b.submit_time);
  const minTime = relevantSubmissions[0].submit_time;
  const maxTime = relevantSubmissions[relevantSubmissions.length - 1].submit_time;
  const span = maxTime - minTime;

  let stepSeconds;
  if (span === 0) {
    stepSeconds = 3600;
  } else if (span <= 4 * 3600) {
    stepSeconds = 15 * 60;
  } else if (span <= 24 * 3600) {
    stepSeconds = 3600;
  } else if (span <= 3 * 86400) {
    stepSeconds = 4 * 3600;
  } else if (span <= 7 * 86400) {
    stepSeconds = 12 * 3600;
  } else if (span <= 30 * 86400) {
    stepSeconds = 86400;
  } else if (span <= 90 * 86400) {
    stepSeconds = 3 * 86400;
  } else {
    stepSeconds = 7 * 86400;
  }

  const bucketsMap = new Map();
  relevantSubmissions.forEach((sub) => {
    const intervalIndex = Math.floor((sub.submit_time - minTime) / stepSeconds);
    if (!bucketsMap.has(intervalIndex)) {
      bucketsMap.set(intervalIndex, []);
    }
    bucketsMap.get(intervalIndex).push(sub);
  });

  const sortedIntervals = Array.from(bucketsMap.keys()).sort((a, b) => a - b);
  const buckets = sortedIntervals.map((idx) => bucketsMap.get(idx));

  const labels = [];
  const okData = [];
  const notOkData = [];
  const bucketSubmissions = [];
  const bucketColors = [];

  buckets.forEach((bucketSubs) => {
    let okCount = 0,
      failCount = 0;
    let bMinTime = Infinity,
      bMaxTime = 0;

    bucketSubs.forEach((s) => {
      if (s.result_id === VERDICT_ACCEPTED) {
        okCount++;
      } else {
        failCount++;
      }
      if (s.submit_time < bMinTime) {
        bMinTime = s.submit_time;
      }
      if (s.submit_time > bMaxTime) {
        bMaxTime = s.submit_time;
      }
    });

    const progress = BacsUtils.getContestProgress(bMinTime, contestData.starttime, contestData.endtime);
    const timeFromStartStr = BacsUtils.formatTime(bMinTime - contestData.starttime);

    labels.push([`+ ${timeFromStartStr}`, progress.text]);
    bucketColors.push(progress.color);
    okData.push(okCount);
    notOkData.push(failCount);
    bucketSubmissions.push(bucketSubs);
  });

  const TEXT_COLOR = '#6b7280';
  const GRID_COLOR = 'rgba(0, 0, 0, 0.04)';
  const tooltipConfig = BacsUtils.getTooltipBaseConfig({
    title: function(context) {
      if (context[0]) {
        const idx = context[0].dataIndex;
        const bucket = bucketSubmissions[idx];
        let minT = Infinity,
          maxT = 0;
        bucket.forEach((s) => {
          if (s.submit_time < minT) {
            minT = s.submit_time;
          }
          if (s.submit_time > maxT) {
            maxT = s.submit_time;
          }
        });
        const minStr = BacsUtils.formatFullDate(minT);
        const maxStr = BacsUtils.formatFullDate(maxT);
        const rangeStr = minT === maxT ? minStr : `${minStr} —\n${maxStr}`;
        const lblTime = labels[idx][0];
        const lblPct = labels[idx][1];
        const elapsedStr = lblPct ? `${lblTime} (${lblPct})` : lblTime;
        return [`📅 Period:`, `${rangeStr}`, ``, `⏱️ Elapsed: ${elapsedStr}`];
      }
      return '';
    },
  });
  tooltipConfig.mode = 'index';
  tooltipConfig.intersect = false;

  const ctx = canvas.getContext('2d');
  window.taskDynamicsChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: contestData.localizedStrings.verdict_ok || 'Accepted',
          data: okData,
          backgroundColor: '#10b981',
          borderColor: '#059669',
          borderWidth: 1,
          borderRadius: 4,
          categoryPercentage: 0.9,
          barPercentage: 1.0,
        },
        {
          label: contestData.localizedStrings.verdict_not_ok || 'Failed',
          data: notOkData,
          backgroundColor: '#f43f5e',
          borderColor: '#e11d48',
          borderWidth: 1,
          borderRadius: 4,
          categoryPercentage: 0.9,
          barPercentage: 1.0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {padding: {top: 20, right: 20, left: 10, bottom: 10}},
      scales: {
        x: {
          stacked: true,
          title: {display: false},
          border: {display: false},
          grid: {display: false},
          ticks: {
            maxRotation: 0,
            font: {size: 11, family: "'Inter', monospace", weight: '600'},
            color: (c) => [TEXT_COLOR, bucketColors[c.index] || TEXT_COLOR],
          },
        },
        y: {
          stacked: true,
          beginAtZero: true,
          border: {display: false},
          title: {
            display: true,
            text: contestData.localizedStrings.submits || 'Submissions',
            color: TEXT_COLOR,
            font: {weight: '500'},
          },
          ticks: {color: TEXT_COLOR, precision: 0, padding: 10},
          grid: {color: GRID_COLOR, drawBorder: false},
        },
      },
      animation: {duration: 1000, easing: 'easeOutQuart'},
      plugins: {
        legend: {position: 'top', labels: {color: '#374151', boxWidth: 12, usePointStyle: true}},
        tooltip: tooltipConfig,
      },
      onClick: (e, elements) => {
        if (!elements || elements.length === 0) {
          return;
        }
        const index = elements[0].index;
        const subsInBucket = bucketSubmissions[index];

        if (subsInBucket && subsInBucket.length > 0) {
          subsInBucket.sort((a, b) => b.submit_time - a.submit_time);

          if (detailsTitle) {
            detailsTitle.innerHTML = `<i class="bi bi-list-check me-2 text-primary">
            </i> Submissions details <span class="badge bg-secondary ms-2">${subsInBucket.length} total</span>`;
          }
          if (detailsTableBody) {
            detailsTableBody.innerHTML = subsInBucket
              .map((sub) => {
                const studentName = studentMap[sub.user_id] || `User ${sub.user_id}`;
                const taskName = taskMap[sub.task_id] || sub.task_id;
                const isOk = sub.result_id === VERDICT_ACCEPTED;
                const timeFromStartStr = BacsUtils.formatTime(sub.submit_time - contestData.starttime);
                const realDateStr = BacsUtils.formatFullDate(sub.submit_time);
                const statusBadge = isOk
                  ? `<span style="background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 
                  4px; font-size: 0.8rem; font-weight: bold;">OK</span>`
                  : `<span style="background: #ffe4e6; color: #e11d48; padding: 2px 6px; 
                  border-radius: 4px; font-size: 0.8rem; font-weight: bold;">FAIL</span>`;
                const prog = BacsUtils.getContestProgress(sub.submit_time, contestData.starttime, contestData.endtime);
                const barHtml = prog.isUpsolving
                  ? `<div style="font-size: 0.75rem; color: #6b7280; margin-top: 4px;">[Upsolving]</div>`
                  : `<div style="width: 100%; height: 4px; background: #e5e7eb; border-radius: 2px; 
                  margin-top: 6px; overflow: hidden;"><div style="width: ${prog.percent}%; height: 100%; 
                  background: ${prog.color};"></div></div><div style="font-size: 0.7rem; color: ${prog.color}; text-align: right; 
                  line-height: 1;">${prog.percent.toFixed(0)}%</div>`;

                return `<tr><td class="align-middle" style="width: 180px;">
                <div class="fw-bold" style="font-size: 0.85rem; color: #111827;">${realDateStr}</div>
                <div style="font-family: monospace; font-size: 0.8rem; color: #6b7280; margin-top: 2px;">+ ${timeFromStartStr}</div>
                ${barHtml}</td><td class="align-middle fw-500">${studentName}</td>
                <td class="align-middle">${taskName}</td><td class="align-middle">${statusBadge}</td>
                <td class="align-middle fw-bold">${sub.points !== null ? sub.points : '-'}</td></tr>`;
              })
              .join('');
          }

          if (detailsContainer) {
            detailsContainer.style.display = 'block';
            detailsContainer.scrollIntoView({behavior: 'smooth', block: 'start'});
          }
        } else {
          if (detailsContainer) {
            detailsContainer.style.display = 'none';
          }
        }
      },
    },
  });
};