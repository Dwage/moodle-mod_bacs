document.addEventListener('DOMContentLoaded', function() {
  let leaderDynamicsInitialized = false;

  const toggleContainer = document.querySelector('.bacs-view-toggles');
  if (toggleContainer) {
    toggleContainer.addEventListener('click', (e) => {
      const button = e.target.closest('button');
      if (!button) {
 return;
}
      const targetViewId = 'view-' + button.dataset.view;

      toggleContainer.querySelectorAll('button').forEach((btn) => {
        btn.classList.remove('btn-primary', 'active');
        btn.classList.add('btn-secondary');
      });
      button.classList.add('btn-primary', 'active');
      button.classList.remove('btn-secondary');

      document.querySelectorAll('.bacs-view').forEach((view) => {
        view.classList.toggle('active', view.id === targetViewId);
      });

      if (button.dataset.view === 'leader-dynamics' && !leaderDynamicsInitialized) {
        if (typeof window.initializeLeaderDynamicsChart === 'function') {
          window.initializeLeaderDynamicsChart();
          leaderDynamicsInitialized = true;
        }
      }
      if (button.dataset.view === 'results-graph') {
        if (typeof window.renderResultsGraph === 'function') {
          window.renderResultsGraph();
        }
      }
      if (button.dataset.view === 'task-dynamics') {
        if (typeof window.renderTaskDynamicsGraph === 'function') {
          if (!window.taskDynamicsChartInstance) {
            window.renderTaskDynamicsGraph();
          }
        }
      }
    });
  }

  const taskSelect = document.getElementById('task-dynamics-select');
  if (taskSelect) {
    if (taskSelect.value === '0' && taskSelect.options.length > 1) {
      taskSelect.selectedIndex = 1;
    }
    taskSelect.addEventListener('change', () => {
      if (typeof window.renderTaskDynamicsGraph === 'function') {
 window.renderTaskDynamicsGraph();
}
    });
  }

  if (typeof window.renderTaskDynamicsGraph === 'function') {
    window.renderTaskDynamicsGraph();
  }
});
