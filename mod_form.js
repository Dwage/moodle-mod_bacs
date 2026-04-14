/* eslint-disable no-console */
/* eslint-disable complexity */
/* eslint-disable max-len */
/* global flatpickr, openPointsModal, Sortable*/
document.addEventListener('DOMContentLoaded', function () {
  const getEl = (id) => document.getElementById(id);

  function formatTime(ms) {
    return !ms || ms === '0' ? '-' : parseInt(ms) / 1000 + 's';
  }
  function formatMemory(bytes) {
    return !bytes || bytes === '0' ? '-' : Math.round(parseInt(bytes) / (1024 * 1024)) + 'MB';
  }
  function escapeHtml(text) {
    return !text
      ? ''
      : text.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
  }
  function parseRawString(str) {
    return !str
      ? []
      : str
          .split(/[\s,]+/)
          .map((s) => s.trim())
          .filter((s) => s !== '');
  }

  const icons = {
    time: `<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none">
    <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`,
    memory: `<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none">
    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
    <line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>`,
    tests: `<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none">
    <polyline points="9 11 12 14 22 4"></polyline>
    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>`,
    remove: `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
    settings: `<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
    <circle cx="12" cy="12" r="3"></circle>
    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1.82 1.82l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`,
  };

  // FLATPICKR
  try {
    const startInput = getEl('modern_starttime');
    const endInput = getEl('modern_endtime');

    if (startInput && endInput && typeof flatpickr !== 'undefined') {
      const getMoodleSelect = (prefix, type) => document.querySelector(`select[name="${prefix}[${type}]"]`);

      const getMoodleDate = (prefix) => {
        const y = getMoodleSelect(prefix, 'year');
        if (!y) {
          return new Date();
        }
        return new Date(
          getMoodleSelect(prefix, 'year').value,
          getMoodleSelect(prefix, 'month').value - 1,
          getMoodleSelect(prefix, 'day').value,
          getMoodleSelect(prefix, 'hour').value,
          getMoodleSelect(prefix, 'minute').value,
        );
      };

      const updateSelects = (prefix, d) => {
        const els = {
          day: getMoodleSelect(prefix, 'day'),
          month: getMoodleSelect(prefix, 'month'),
          year: getMoodleSelect(prefix, 'year'),
          hour: getMoodleSelect(prefix, 'hour'),
          min: getMoodleSelect(prefix, 'minute'),
        };
        if (els.d) {
          els.d.value = d.getDate();
        }
        if (els.month) {
          els.month.value = d.getMonth() + 1;
        }
        if (els.year) {
          els.year.value = d.getFullYear();
        }
        if (els.hour) {
          els.hour.value = d.getHours();
        }
        if (els.min) {
          els.min.value = d.getMinutes();
        }
      };

      let fpStart = flatpickr(startInput, {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        time_24hr: true,
        allowInput: true,
        defaultDate: getMoodleDate('starttime'),
        onChange: function (selectedDates) {
          if (selectedDates[0]) {
            updateSelects('starttime', selectedDates[0]);
            fpEnd.set('minDate', selectedDates[0]);
          }
        },
      });

      let fpEnd = flatpickr(endInput, {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        time_24hr: true,
        allowInput: true,
        defaultDate: getMoodleDate('endtime'),
        minDate: getMoodleDate('starttime'),
        onChange: function (selectedDates) {
          if (selectedDates[0]) {
            updateSelects('endtime', selectedDates[0]);
          }
        },
      });

      startInput.addEventListener('blur', function () {
        if (fpStart.selectedDates[0]) {
          updateSelects('starttime', fpStart.selectedDates[0]);
          fpEnd.set('minDate', fpStart.selectedDates[0]);
        }
      });
      endInput.addEventListener('blur', function () {
        if (fpEnd.selectedDates[0]) {
          updateSelects('endtime', fpEnd.selectedDates[0]);
        }
      });
    }
  } catch (err) {
    console.warn('Flatpickr error:', err);
  }

  try {
    const modeSelect = getEl('id_mode_select');
    const modeCards = document.querySelectorAll('.mode-card');
    if (modeSelect && modeCards.length) {
      modeCards.forEach((c) =>
        c.addEventListener('click', function () {
          modeSelect.value = this.dataset.value;
          modeCards.forEach((card) => card.classList.toggle('active', card === this));
        }),
      );
      const activeCard = document.querySelector(`.mode-card[data-value="${modeSelect.value}"]`);
      if (activeCard) {
        activeCard.classList.add('active');
      }
    }
  } catch (err) {}

  // Tasks data base
  if (typeof window.BACS_FORM_DATA === 'undefined') {
    return;
  }

  function getRatingBadgeClass(rVal) {
    if (rVal > 1500) {
      return 'bg-danger text-white border-danger';
    }
    if (rVal < 1000) {
      return 'bg-success text-white border-success';
    }
    return 'bg-warning text-dark border-warning';
  }

  const DATA = window.BACS_FORM_DATA;
  const allTasks = DATA.tasks || [];
  let selectedIds = (DATA.selectedTaskIds || []).map(String);
  let pointsMap = DATA.savedTestPoints || {};

  function updateHiddenInputs() {
    const idInput = getEl('id_contest_task_ids');
    const ptInput = getEl('id_contest_task_test_points');
    if (idInput) {
      idInput.value = selectedIds.join('_');
    }
    if (ptInput) {
      ptInput.value = selectedIds.map((id) => pointsMap[id] || '').join('_');
    }
  }

  getEl('id_contest_task_ids')?.addEventListener('change', (e) => {
    selectedIds = e.target.value.split('_').filter((i) => i);
    renderAll();
  });
  getEl('id_contest_task_test_points')?.addEventListener('change', (e) => {
    const pts = e.target.value.split('_');
    selectedIds.forEach((id, i) => {
      if (pts[i] !== undefined) {
        pointsMap[id] = pts[i];
      }
    });
    renderAll();
  });

  function addTask(task) {
    const id = String(task.task_id);
    if (!selectedIds.includes(id)) {
      selectedIds.push(id);
      if (!pointsMap[id]) {
        pointsMap[id] = task.default_points || '';
      }
      renderAll();
    }
  }

  function removeTask(id) {
    selectedIds = selectedIds.filter((i) => i !== String(id));
    renderAll();
  }

  function renderAll() {
    renderModernSource();
    renderModernTarget();
    renderClassicTarget();
    updateHiddenInputs();
  }

  window.collectionSelectorChange = function () {
    const selector = getEl('collection_container_selector');
    if (!selector) {
      return;
    }
    document.querySelectorAll('.classic-tasks-container').forEach((c) => {
      c.style.display = 'none';
    });
    const curr = getEl('collection_container_' + selector.value);
    if (curr) {
      curr.style.display = 'block';
      apply_sort();
    }
  };

  window.tableSearch = function () {
    const phrase = getEl('search-text').value.toLowerCase();
    const selector = getEl('collection_container_selector');
    const currContainer = getEl('collection_container_' + selector.value);
    if (!currContainer) {
      return;
    }
    currContainer.querySelectorAll('tbody tr').forEach((row) => {
      let visible = false;
      Array.from(row.children).forEach((cell, i) => {
        if (i !== row.children.length - 1 && cell.textContent.toLowerCase().includes(phrase)) {
          visible = true;
        }
      });
      row.style.display = visible ? '' : 'none';
    });
  };

  window.cleanSearch = function () {
    const elem = getEl('search-text');
    if (elem) {
      elem.value = '';
      window.tableSearch();
    }
  };

  window.addTaskClassic = function (taskId) {
    const task = allTasks.find((t) => String(t.task_id) === String(taskId));
    if (task) {
      addTask(task);
    }
  };

  // Classic table
  function renderClassicTarget() {
    const list = getEl('classic_tasks_reorder_list');
    if (!list) {
      return;
    }
    list.innerHTML = '';

    selectedIds.forEach((id, index) => {
      let task = allTasks.find((t) => String(t.task_id) === String(id));
      let isMissing = false;

      if (!task) {
        isMissing = true;
        task = {
          task_id: id,
          name: `[TASK NOT FOUND, ID = ${id}]`,
          author: 'unknown',
          statement_format: 'ERR',
          count_tests: 0,
          count_pretests: 0,
          default_points: '',
        };
      }

      const letter = String.fromCharCode(65 + index);
      const pts = parseRawString(pointsMap[id] || task.default_points || '');
      const fullPoints = pts.length > 0 ? parseInt(pts[0]) || 0 : 0;
      const testPointsArr = pts.slice(1).map((p) => parseInt(p) || 0);
      const sumOfTests = testPointsArr.reduce((a, b) => a + b, 0);
      const totalSum = fullPoints + sumOfTests;

      let statusClass = 'task-status-incomplete';
      let statusIcon = '<i class="bi bi-info-circle text-primary" title="Внимание: Есть тесты с 0 баллов"></i>';

      if (isMissing) {
        statusClass = 'bg-danger bg-opacity-10 border-danger';
        statusIcon =
          '<i class="bi bi-exclamation-triangle-fill text-danger ms-2" title="ЗАДАЧА УДАЛЕНА ИЗ БАЗЫ! Удалите её из контеста."></i>';
      } else {
        const pretestsCount = parseInt(task.count_pretests) || 0;
        const mainTests = testPointsArr.slice(pretestsCount);

        if (mainTests.length > 0) {
          const hasZeroInMain = mainTests.includes(0);
          if (!hasZeroInMain) {
            statusClass = 'task-status-complete';
            statusIcon = '<i class="bi bi-check2-circle text-success" title="Все основные тесты настроены"></i>';
          }
        } else if (testPointsArr.length > 0 && pretestsCount >= testPointsArr.length) {
          statusClass = 'task-status-complete';
          statusIcon = '<i class="bi bi-check2-circle text-success" title="Только претесты"></i>';
        }
      }

      let ratingBadge = '';
      if (!isMissing && task.elo_rating !== null && task.elo_rating !== undefined) {
        let rVal = Math.round(parseFloat(task.elo_rating));
        let badgeColor = getRatingBadgeClass(rVal);
        const starColor = badgeColor.includes('text-dark') ? 'text-dark' : 'text-white';
        ratingBadge = `<span class="badge ${badgeColor} shadow-sm ms-2" title="Рейтинг задачи: ${rVal}"><i class="bi bi-star-fill ${starColor} me-1"></i>${rVal}</span>`;
      }

      const settingsBtn = isMissing
        ? `<button type="button" class="btn btn-sm btn-light border text-muted opacity-50" disabled title="Невозможно настроить удаленную задачу"><i class="bi bi-gear-fill"></i></button>`
        : `<button type="button" class="btn btn-sm btn-light border btn-classic-settings text-secondary" title="Настроить баллы"><i class="bi bi-gear-fill"></i></button>`;

      const row = document.createElement('div');
      row.className = `list-group-item d-flex justify-content-between align-items-center px-3 py-2 bg-white ${statusClass}`;
      row.style.cursor = 'default';
      row.dataset.id = id;

      row.innerHTML = `
            <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                <div class="text-muted me-3 drag-handle" style="cursor: grab; font-size: 1.2rem;" title="Drag to reorder">⋮⋮</div>
                <div class="fw-bold ${isMissing ? 'text-danger' : 'text-dark'} me-3 fs-5" style="width: 25px; flex-shrink: 0;">${letter}.</div>
                <div class="d-flex flex-column text-truncate pe-3">
                    <span class="${isMissing ? 'text-danger fw-bold' : 'text-dark fw-medium'} text-truncate d-flex align-items-center" title="${escapeHtml(task.name)}">
                        <span class="text-truncate" style="max-width: 300px;">${escapeHtml(task.name)}</span>
                        <span class="ms-1">${statusIcon}</span>
                        ${ratingBadge}
                    </span>
                    <span class="${isMissing ? 'text-danger' : 'text-muted'}" style="font-size: 0.75rem; font-family: monospace;">ID: ${task.task_id}</span>
                </div>
            </div>
            
            <div class="task-actions-wrapper ms-auto">
                <div class="text-end d-none d-md-block ${isMissing ? 'text-danger' : 'text-muted'}" style="font-size: 0.8rem; line-height: 1.3;">
                    <div>${task.count_tests || 0} tests • ${task.count_pretests || 0} pre</div>
                    <div><strong class="${isMissing ? 'text-danger' : 'text-dark'}">Σ: ${totalSum}</strong> (Full: ${fullPoints})</div>
                </div>
                
                <div class="btn-group shadow-sm">
                    ${settingsBtn}
                    <button type="button" class="btn btn-sm btn-light border btn-classic-remove text-danger" title="Удалить из контеста"><i class="bi bi-trash3-fill"></i></button>
                </div>
            </div>
        `;

      if (!isMissing) {
        row.querySelector('.btn-classic-settings').addEventListener('click', (e) => {
          e.stopPropagation();
          openPointsModal(task);
        });
      }
      row.querySelector('.btn-classic-remove').addEventListener('click', (e) => {
        e.stopPropagation();
        removeTask(id);
      });

      list.appendChild(row);
    });

    if (!window.classicSortableInit && typeof Sortable !== 'undefined') {
      Sortable.create(list, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'bg-light',
        onEnd: function () {
          const newOrder = [];
          list.querySelectorAll('.list-group-item').forEach((el) => newOrder.push(el.dataset.id));
          selectedIds = newOrder;
          renderAll();
        },
      });
      window.classicSortableInit = true;
    }
  }

  // Task manager modal
  const managerModal = getEl('bacs-manager-modal');
  const btnToggleStmt = getEl('toggle-statement-btn');
  const colStatement = document.querySelector('.col-statement');
  const managerGrid = document.querySelector('.manager-grid');
  const managerCols = document.querySelectorAll('.manager-col');

  if (btnToggleStmt && colStatement) {
    const isHidden = localStorage.getItem('bacs_stmt_hidden') === 'true';
    if (isHidden) {
      colStatement.classList.add('col-hidden');
      btnToggleStmt.innerHTML = '👁‍🗨 Show Statement';
    }

    btnToggleStmt.addEventListener('click', () => {
      colStatement.classList.toggle('col-hidden');
      const hiddenNow = colStatement.classList.contains('col-hidden');
      localStorage.setItem('bacs_stmt_hidden', hiddenNow);
      btnToggleStmt.innerHTML = hiddenNow ? '👁‍🗨 Show Statement' : '👁 Hide Statement';
    });
  }

  // Drag&drop
  try {
    const savedOrder = JSON.parse(localStorage.getItem('bacs_col_order'));
    if (savedOrder && Array.isArray(savedOrder) && savedOrder.length === 3) {
      savedOrder.forEach((colName) => {
        const colEl = document.querySelector(`.manager-col[data-col="${colName}"]`);
        if (colEl) {
          managerGrid.appendChild(colEl);
        }
      });
    }
  } catch (e) {}

  let draggedColumn = null;
  managerCols.forEach((col) => {
    const handle = col.querySelector('.col-drag-handle');
    if (handle) {
      handle.addEventListener('mousedown', () => col.setAttribute('draggable', 'true'));
      handle.addEventListener('mouseup', () => col.setAttribute('draggable', 'false'));
      handle.addEventListener('mouseleave', () => col.setAttribute('draggable', 'false'));
    }

    col.addEventListener('dragstart', function (e) {
      if (e.target !== col) {
        return;
      }
      draggedColumn = this;
      managerGrid.classList.add('is-dragging');
      setTimeout(() => this.classList.add('col-dragging'), 0);
      e.dataTransfer.effectAllowed = 'move';
      if (e.dataTransfer.setData) {
        e.dataTransfer.setData('text/plain', 'col');
      }
    });

    col.addEventListener('dragenter', function () {
      if (!draggedColumn || draggedColumn === this) {
        return;
      }
      const children = Array.from(managerGrid.children);
      const draggedIdx = children.indexOf(draggedColumn);
      const thisIdx = children.indexOf(this);
      if (draggedIdx < thisIdx) {
        this.after(draggedColumn);
      } else {
        this.before(draggedColumn);
      }
    });

    col.addEventListener('dragover', function (e) {
      if (draggedColumn) {
        e.preventDefault();
      }
    });

    col.addEventListener('dragend', function () {
      managerGrid.classList.remove('is-dragging');
      this.classList.remove('col-dragging');
      this.setAttribute('draggable', 'false');
      draggedColumn = null;
      const newOrder = Array.from(managerGrid.querySelectorAll('.manager-col')).map((c) => c.dataset.col);
      localStorage.setItem('bacs_col_order', JSON.stringify(newOrder));
    });
  });

  getEl('open-task-manager-btn')?.addEventListener('click', () => {
    managerModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    renderAll();
  });
  document.querySelectorAll('.close-manager-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      managerModal.classList.add('hidden');
      document.body.style.overflow = '';
      updateHiddenInputs();
    });
  });

  const sourceList = getEl('manager-source-list');
  const targetList = getEl('manager-target-list');
  const managerSearch = getEl('manager-search');
  const managerCollection = getEl('manager-collection');

  if (managerCollection && managerCollection.options.length === 0) {
    let optAll = document.createElement('option');
    optAll.value = 'all';
    optAll.textContent = 'Все коллекции (All)';
    managerCollection.appendChild(optAll);
    if (DATA.collections) {
      DATA.collections.forEach((c) => {
        let opt = document.createElement('option');
        opt.value = c.collection_id;
        opt.textContent = c.name;
        managerCollection.appendChild(opt);
      });
    }
  }

  if (managerSearch) {
    managerSearch.addEventListener('keyup', renderModernSource);
  }
  if (managerCollection) {
    managerCollection.addEventListener('change', renderModernSource);
  }

  function renderModernSource() {
    if (!sourceList) {
      return;
    }
    sourceList.innerHTML = '';
    const searchPhrase = managerSearch ? managerSearch.value.toLowerCase() : '';
    const selectedCol = managerCollection ? managerCollection.value : 'all';

    allTasks.forEach((task) => {
      if (selectedIds.includes(String(task.task_id))) {
        return;
      }
      if (selectedCol !== 'all' && String(task.collection_id) !== selectedCol) {
        return;
      }
      const matchSearch = task.name.toLowerCase().includes(searchPhrase) || String(task.task_id).includes(searchPhrase);
      if (searchPhrase && !matchSearch) {
        return;
      }

      const el = document.createElement('div');
      el.className = 'm-task-card';

      let ratingHtml = '';
      if (task.elo_rating !== null && task.elo_rating !== undefined) {
        let rVal = Math.round(parseFloat(task.elo_rating));
        let badgeClass = getRatingBadgeClass(rVal);
        ratingHtml = `<span class="badge ${badgeClass} ms-1" style="font-size:0.7rem;">
    <i class="bi bi-star-fill me-1"></i>${rVal}</span>`;
      }

      el.innerHTML = `
            <div>
                <div class="mt-head">
                    <div class="mt-title-wrapper">
                        <span class="mt-title" title="${escapeHtml(task.name)}">${escapeHtml(task.name)}</span>
                        ${ratingHtml}
                    </div>
                    <span class="mt-id">#${task.task_id}</span>
                </div>
                <div class="mt-meta">
                    <span class="mt-meta-item" title="Time Limit">${icons.time} ${formatTime(task.time_limit_millis)}</span>
                    <span class="mt-meta-item" title="Memory Limit">${icons.memory} ${formatMemory(task.memory_limit_bytes)}</span>
                    <span class="mt-meta-item" title="Tests Count">${icons.tests} ${task.count_tests || '?'} tst</span>
                </div>
            </div>
            <div class="mt-foot">
                <span class="mt-badge">${task.statement_format || 'PDF'}</span>
                <button type="button" class="btn-action-add">+ Add</button>
            </div>`;

      el.addEventListener('click', () => loadStatement(task, el));
      el.querySelector('.btn-action-add').addEventListener('click', (e) => {
        e.stopPropagation();
        addTask(task);
      });
      sourceList.appendChild(el);
    });
  }

  function renderModernTarget() {
    if (!targetList) {
      return;
    }
    targetList.innerHTML = '';
    if (getEl('selected-count-badge')) {
      getEl('selected-count-badge').textContent = selectedIds.length;
    }

    selectedIds.forEach((id, index) => {
      let task = allTasks.find((t) => String(t.task_id) === String(id));
      let isMissing = false;

      if (!task) {
        isMissing = true;
        task = { task_id: id, name: `[TASK NOT FOUND, ID=${id}]`, statement_format: 'ERR', count_tests: 0 };
      }

      const el = document.createElement('div');
      el.className = `m-task-card ${isMissing ? 'bg-danger bg-opacity-10 border-danger' : ''}`;
      el.dataset.id = id;

      const settingsBtn = isMissing
        ? `<button type="button" class="btn-action-settings opacity-50" disabled>${icons.settings}</button>`
        : `<button type="button" class="btn-action-settings" title="Points Settings">${icons.settings}</button>`;

      el.innerHTML = `
            <div>
                <div class="mt-head">
                    <div class="mt-title-wrapper">
                        <span class="mt-order ${isMissing ? 'text-danger border-danger' : ''}">${index + 1}</span>
                        <span class="mt-title ${isMissing ? 'text-danger fw-bold' : ''}" title="${escapeHtml(task.name)}">${escapeHtml(task.name)}</span>
                    </div>
                    <button type="button" class="btn-action-remove" title="Remove Task">${icons.remove}</button>
                </div>
                <div class="mt-meta">
                    <span class="mt-meta-item ${isMissing ? 'text-danger' : ''}" title="Tests Count">${icons.tests} ${task.count_tests || '?'} tst</span>
                </div>
            </div>
            <div class="mt-foot">
                <span class="mt-badge ${isMissing ? 'bg-danger text-white' : ''}">${task.statement_format || 'PDF'}</span>
                <div>${settingsBtn}</div>
            </div>`;

      if (!isMissing) {
        el.addEventListener('click', () => loadStatement(task, el));
        el.querySelector('.btn-action-settings').addEventListener('click', (e) => {
          e.stopPropagation();
          openPointsModal(task);
        });
      }
      el.querySelector('.btn-action-remove').addEventListener('click', (e) => {
        e.stopPropagation();
        removeTask(id);
      });

      targetList.appendChild(el);
    });

    if (!window.modernSortableInit && typeof Sortable !== 'undefined') {
      Sortable.create(targetList, {
        animation: 150,
        ghostClass: 'bg-light',
        onEnd: function () {
          const newOrder = [];
          targetList.querySelectorAll('.m-task-card').forEach((el) => newOrder.push(el.dataset.id));
          selectedIds = newOrder;
          renderAll();
        },
      });
      window.modernSortableInit = true;
    }
  }

  function loadStatement(task, cardElement) {
    document.querySelectorAll('.m-task-card').forEach((c) => c.classList.remove('active-view'));
    cardElement.classList.add('active-view');

    const frame = getEl('statement-frame'),
      img = getEl('statement-image'),
      placeholder = getEl('statement-placeholder'),
      extLink = getEl('statement-external-link');

    if (frame) {
      frame.classList.add('hidden');
      frame.removeAttribute('src');
    }
    if (img) {
      img.classList.add('hidden');
    }
    if (placeholder) {
      placeholder.style.display = 'none';
    }
    if (extLink) {
      extLink.classList.add('hidden');
    }

    if (!task.statement_url) {
      if (placeholder) {
        placeholder.style.display = 'block';
        placeholder.innerHTML = `<p>No statement available</p>`;
      }
      return;
    }

    if (extLink) {
      extLink.href = task.statement_url;
      extLink.classList.remove('hidden');
    }

    let format = (task.statement_format || '').toLowerCase();
    const urlLower = task.statement_url.toLowerCase();
    if (!format || format === 'unknown') {
      if (urlLower.endsWith('.pdf')) {
        format = 'pdf';
      } else if (urlLower.match(/\.(jpg|jpeg|png|gif|svg)$/)) {
        format = 'image';
      } else if (urlLower.endsWith('.docx') || urlLower.endsWith('.doc')) {
        format = 'doc';
      } else {
        format = 'html';
      }
    }

    if (['pdf', 'html', 'txt'].includes(format) && frame) {
      frame.classList.remove('hidden');
      frame.src = task.statement_url;
    } else if (['image', 'jpg', 'jpeg', 'png'].includes(format) && img) {
      img.classList.remove('hidden');
      img.src = task.statement_url;
    } else if (['doc', 'docx'].includes(format) && frame) {
      frame.classList.remove('hidden');
      frame.src = `https://docs.google.com/gview?url=${encodeURIComponent(task.statement_url)}&embedded=true`;
    }
  }

  // Tests points modal
  const ptsModal = getEl('test-points-modal');
  const ptsRawInput = getEl('modal-points-input');
  const modalFullPoints = getEl('modal-full-points');

  let editingId = null;
  let currentModalTestCount = 0;
  let currentModalPretestCount = 0;

  window.openPointsModal = function (task) {
    editingId = String(task.task_id);
    currentModalTestCount = parseInt(task.count_tests) || 0;
    currentModalPretestCount = parseInt(task.count_pretests) || 0;
    getEl('modal-task-name').textContent = `${task.name}`;

    let savedValues = parseRawString(pointsMap[editingId] || task.default_points || '');
    let fullPts = savedValues.length > 0 ? parseInt(savedValues[0]) || 0 : 0;
    if (modalFullPoints) {
      modalFullPoints.value = fullPts;
    }

    let testValues = savedValues.slice(1);
    if (currentModalTestCount === 0 && testValues.length > 0) {
      currentModalTestCount = testValues.length;
    }

    renderGrid(currentModalTestCount, currentModalPretestCount, testValues);
    updateRawFromGrid();
    ptsModal.classList.remove('hidden');
  };

  function renderGrid(count, pretestsCount, values = []) {
    const visualGrid = getEl('visual-points-grid');
    visualGrid.innerHTML = '';

    for (let i = 0; i < count; i++) {
      const val = values[i] !== undefined ? values[i] : '0';
      const isPretest = i < pretestsCount;

      const div = document.createElement('div');
      div.className = 'point-item';

      const inputClass = isPretest ? 'grid-input pretest-input' : 'grid-input';
      const labelText = isPretest ? `<span class="text-warning">Pre ${i + 1}</span>` : `${i + 1}`;

      div.innerHTML = `<label>${labelText}</label><input type="text" class="${inputClass}" data-index="${i}" value="${val}" title="${isPretest ? 'Pretest' : 'Main Test'}">`;
      visualGrid.appendChild(div);
    }
    visualGrid.querySelectorAll('.grid-input').forEach((inp) => {
      inp.addEventListener('input', updateRawFromGrid);
    });
  }

  function updateRawFromGrid() {
    let fullPts = parseInt(modalFullPoints?.value) || 0;
    let arr = [fullPts];
    let gridSum = 0;

    getEl('visual-points-grid')
      .querySelectorAll('.grid-input')
      .forEach((inp) => {
        let val = parseInt(inp.value) || 0;
        arr.push(val);
        gridSum += val;
      });

    if (ptsRawInput) {
      ptsRawInput.value = arr.join(',');
    }
    if (getEl('points-total-sum')) {
      getEl('points-total-sum').textContent = fullPts + gridSum;
    }
  }

  if (modalFullPoints) {
    modalFullPoints.addEventListener('input', updateRawFromGrid);
  }
  ptsRawInput?.addEventListener('input', () => {
    let vals = parseRawString(ptsRawInput.value);
    if (vals.length > 0 && modalFullPoints) {
      modalFullPoints.value = vals[0];
    }
    let tests = vals.slice(1);

    getEl('visual-points-grid')
      .querySelectorAll('.grid-input')
      .forEach((inp, i) => {
        if (tests[i] !== undefined) {
          inp.value = tests[i];
        }
      });
    updateRawFromGrid();
  });

  getEl('btn-range-apply')?.addEventListener('click', () => {
    const s = parseInt(getEl('range-start').value) || 1;
    const e = parseInt(getEl('range-end').value) || currentModalTestCount;
    const val = getEl('range-val').value || '0';

    const inputs = getEl('visual-points-grid').querySelectorAll('.grid-input');
    const startIdx = Math.max(1, s) - 1;
    const endIdx = Math.min(inputs.length, e);

    for (let i = startIdx; i < endIdx; i++) {
      if (inputs[i]) {
        inputs[i].value = val;
      }
    }
    updateRawFromGrid();
  });

  document
    .querySelectorAll('.btn-preset')
    .forEach((btn) => btn.addEventListener('click', () => applyValueToGrid(btn.dataset.val)));
  getEl('btn-clear-grid')?.addEventListener('click', () => applyValueToGrid(0));

  function applyValueToGrid(val) {
    getEl('visual-points-grid')
      .querySelectorAll('.grid-input')
      .forEach((inp) => {
        inp.value = val;
      });
    updateRawFromGrid();
  }

  getEl('btn-normalize')?.addEventListener('click', () => {
    const targetSum = parseInt(getEl('norm-target').value) || 100;
    const includePretests = getEl('norm-include-pretests') ? getEl('norm-include-pretests').checked : false;

    const allInputs = Array.from(getEl('visual-points-grid').querySelectorAll('.grid-input'));
    if (allInputs.length === 0) {
      return;
    }

    const inputsToNormalize = [];
    allInputs.forEach((inp, idx) => {
      const isPretest = idx < currentModalPretestCount;
      if (!isPretest || includePretests) {
        inputsToNormalize.push(inp);
      }
    });

    if (inputsToNormalize.length === 0) {
      return;
    }

    let currentValues = inputsToNormalize.map((inp) => Math.max(0, parseFloat(inp.value) || 0));
    let currentSum = currentValues.reduce((a, b) => a + b, 0);
    let newValues = [];

    if (currentSum === 0) {
      const base = Math.floor(targetSum / inputsToNormalize.length);
      const remainder = targetSum % inputsToNormalize.length;
      for (let i = 0; i < inputsToNormalize.length; i++) {
        newValues[i] = base + (i < remainder ? 1 : 0);
      }
    } else {
      let floorValues = [],
        remainderParts = [];
      for (let i = 0; i < inputsToNormalize.length; i++) {
        let ideal = (currentValues[i] / currentSum) * targetSum;
        let floored = Math.floor(ideal);
        floorValues[i] = floored;
        remainderParts.push({ index: i, fraction: ideal - floored });
      }
      let diff = targetSum - floorValues.reduce((a, b) => a + b, 0);
      remainderParts.sort((a, b) => b.fraction - a.fraction);
      for (let i = 0; i < diff; i++) {
        floorValues[remainderParts[i % inputsToNormalize.length].index]++;
      }
      newValues = floorValues;
    }

    inputsToNormalize.forEach((inp, i) => {
      inp.value = newValues[i];
    });
    updateRawFromGrid();
  });

  getEl('save-points-btn')?.addEventListener('click', () => {
    if (!editingId) {
      return;
    }
    pointsMap[editingId] = ptsRawInput.value;
    renderAll();
    ptsModal.classList.add('hidden');
  });

  ptsModal.querySelector('.close-modal')?.addEventListener('click', () => ptsModal.classList.add('hidden'));

  function apply_sort() {
    var collSelect = document.getElementById('collection_container_selector');
    if (!collSelect) {
      return;
    }

    var container = document.getElementById('collection_container_' + collSelect.value);
    if (!container) {
      return;
    }

    var sortSelect = document.getElementById('bacs_sort_selector');
    if (!sortSelect || !sortSelect.value) {
      return;
    }

    var dir = sortSelect.value.split('_')[1];

    var tbody = container.querySelector('table tbody');
    if (!tbody) {
      return;
    }

    var rows = Array.from(tbody.rows);
    rows.sort(function (rowA, rowB) {
      var a = parseFloat(rowA.dataset.rating) || 0;
      var b = parseFloat(rowB.dataset.rating) || 0;
      return dir === 'asc' ? a - b : b - a;
    });
    rows.forEach(function (row) {
      tbody.appendChild(row);
    });
  }

  var sortSelector = document.getElementById('bacs_sort_selector');
  if (sortSelector) {
    sortSelector.addEventListener('change', apply_sort);
  }

  renderAll();
});
