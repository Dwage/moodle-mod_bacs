/* eslint-disable */
/* jshint ignore:start */
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Difficulty analysis module for BACS contests
 *
 * @copyright  SybonTeam, sybon.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function () {
    'use strict';

    // jQuery will be available when functions are called
    // We check for it in each function that uses it

    let chartInstance = null;
    let currentCmid = null;
    let strings = {
        notasksselected: 'No tasks selected. Please add tasks to the contest first.',
        students_can_solve: 'Students who can solve the task',
        ideal_curve: 'Ideal curve',
        number_of_students: 'Number of students',
        tasks: 'Tasks'
    };

    /**
     * Show notification using Moodle's notification system
     * @param {string} message - Message to display
     * @param {string} type - Type of notification ('error', 'warning', 'info', 'success')
     */
    function showNotification(message, type) {
        type = type || 'info';
        // Try to use Moodle's notification system if available
        if (typeof M !== 'undefined' && M.util && M.util.add_notification) {
            M.util.add_notification(message, type);
        } else if (typeof Y !== 'undefined' && Y.use) {
            // Fallback to YUI notifications if available
            Y.use('moodle-core-notification-alert', function () {
                new M.core.alert({ message: message });
            });
        } else {
            // Last resort: use alert
            alert(message);
        }
    }

    /**
     * Collects task IDs from the tasks_reorder_list DOM element
     * @returns {Array} Array of task IDs
     */
    function collectTaskIds() {
        const tasksList = document.getElementById('classic_tasks_reorder_list');
        const taskIds = [];

        if (tasksList && tasksList.children.length > 0) {
            for (let i = 0; i < tasksList.children.length; i++) {
                const taskElement = tasksList.children[i];
                const taskId = parseInt(taskElement.dataset.id, 10);
                if (!isNaN(taskId) && taskId > 0) {
                    taskIds.push(taskId);
                }
            }
        }

        return taskIds;
    }

    /**
     * Updates the chart with current task list
     * @param {boolean} silent - If true, don't show loader/errors
     */
    function updateChart(silent) {
        // Ensure jQuery is available
        var $ = window.jQuery || window.$;
        if (typeof $ === 'undefined') {
            console.error('jQuery is not available. Cannot update chart.');
            return;
        }

        if (!currentCmid || currentCmid === 0) {
            console.log('bacsUpdateDifficultyChart: currentCmid not set or is 0');
            return;
        }

        const result = $('#bacs-difficulty-analysis-result');
        const loader = $('#bacs-difficulty-analysis-loader');
        const button = $('#bacs-difficulty-analysis-btn');

        // Check if chart container exists and is visible
        const resultElement = result[0];
        let isVisible = false;

        if (resultElement) {
            const style = window.getComputedStyle(resultElement);
            const isDisplayed = style.display !== 'none';
            const isNotHidden = style.visibility !== 'hidden';
            const hasOffsetParent = resultElement.offsetParent !== null;
            const jqueryVisible = result.is(':visible');

            isVisible = isDisplayed && isNotHidden && (hasOffsetParent || resultElement === document.body || resultElement === document.documentElement);

            if (!isVisible && jqueryVisible) {
                isVisible = true;
            }

            console.log('bacsUpdateDifficultyChart: visibility check', {
                isDisplayed: isDisplayed,
                isNotHidden: isNotHidden,
                hasOffsetParent: hasOffsetParent,
                jqueryVisible: jqueryVisible,
                finalVisible: isVisible,
                display: style.display,
                visibility: style.visibility
            });
        } else {
            console.log('bacsUpdateDifficultyChart: result element not found');
        }

        // Only update if chart is visible
        if (!isVisible) {
            console.log('bacsUpdateDifficultyChart: chart not visible, skipping update');
            return;
        }

        const taskIds = collectTaskIds();
        console.log('bacsUpdateDifficultyChart: collected task IDs:', taskIds);

        if (taskIds.length === 0) {
            console.log('bacsUpdateDifficultyChart: no task IDs found');
            if (!silent) {
                showNotification(strings.notasksselected, 'error');
            }
            return;
        }

        // Show loader if not silent
        if (!silent) {
            loader.show();
        }

        console.log('bacsUpdateDifficultyChart: starting AJAX request with', taskIds.length, 'tasks');

        const sesskey = M.cfg.sesskey;

        $.ajax({
            url: M.cfg.wwwroot + '/mod/bacs/difficulty_analysis_ajax.php',
            type: 'POST',
            data: {
                cmid: currentCmid,
                task_ids: taskIds,
                sesskey: sesskey
            },
            traditional: false,
            dataType: 'json',
            success: function (response) {
                loader.hide();

                if (response.success) {
                    displayChart(response);
                } else {
                    if (!silent) {
                        showNotification(response.error || 'Error analyzing contest difficulty', 'error');
                    }
                }
            },
            error: function (xhr, status, error) {
                loader.hide();
                if (!silent) {
                    showNotification('Error loading analysis: ' + error, 'error');
                }
            }
        });
    }

    /**
     * Display chart with data
     * @param {Object} data - Chart data
     */
    function displayChart(data) {
        const ctx = document.getElementById('bacs-difficulty-chart');
        if (!ctx) {
            return;
        }

        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded');
            showNotification('Chart library is not loaded', 'error');
            return;
        }

        // Destroy existing chart if it exists
        if (chartInstance) {
            chartInstance.destroy();
        }

        // Prepare data
        const taskLabels = data.task_labels;
        const studentsCanSolve = data.students_can_solve;
        const idealCurve = data.ideal_curve;

        // Create chart
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: taskLabels,
                datasets: [
                    {
                        label: strings.students_can_solve,
                        data: studentsCanSolve,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: strings.ideal_curve,
                        data: idealCurve,
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: strings.number_of_students
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: strings.tasks
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    /**
     * Initialize difficulty analysis module
     * @param {number} cmid - Course module ID
     * @param {string} notasksselectedText - Text for no tasks selected
     * @param {string} studentsCanSolveText - Text for students can solve
     * @param {string} idealCurveText - Text for ideal curve
     * @param {string} numberOfStudentsText - Text for number of students
     * @param {string} tasksText - Text for tasks
     */
    window.bacsDifficultyAnalysisInit = function (cmid, notasksselectedText, studentsCanSolveText, idealCurveText, numberOfStudentsText, tasksText) {
        // Ensure jQuery is available
        var $ = window.jQuery || window.$;
        if (typeof $ === 'undefined') {
            console.error('jQuery is not available. Cannot initialize difficulty analysis.');
            return;
        }

        // Store localized strings
        strings.notasksselected = notasksselectedText || 'No tasks selected. Please add tasks to the contest first.';
        strings.students_can_solve = studentsCanSolveText || 'Students who can solve the task';
        strings.ideal_curve = idealCurveText || 'Ideal curve';
        strings.number_of_students = numberOfStudentsText || 'Number of students';
        strings.tasks = tasksText || 'Tasks';
        currentCmid = cmid;

        // Don't initialize if cmid is not set or is 0 (new module creation)
        if (!cmid || cmid === 0) {
            console.log('bacs difficulty_analysis: cmid not set, skipping initialization');
            return;
        }

        const button = $('#bacs-difficulty-analysis-btn');
        const loader = $('#bacs-difficulty-analysis-loader');
        const result = $('#bacs-difficulty-analysis-result');
        const canvas = $('#bacs-difficulty-chart');

        button.on('click', function () {
            // Check if cmid is valid before making request
            if (!cmid || cmid === 0) {
                showNotification('Cannot analyze difficulty: module not saved yet', 'error');
                return;
            }
            // Show loader, hide result
            loader.show();
            result.hide();
            button.prop('disabled', true);

            const taskIds = collectTaskIds();

            // Debug: log collected task IDs
            console.log('Collected task IDs:', taskIds);

            if (taskIds.length === 0) {
                loader.hide();
                button.prop('disabled', false);
                showNotification(strings.notasksselected, 'error');
                return;
            }

            // Get sesskey
            const sesskey = M.cfg.sesskey;

            // Make AJAX request with task IDs
            $.ajax({
                url: M.cfg.wwwroot + '/mod/bacs/difficulty_analysis_ajax.php',
                type: 'POST',
                data: {
                    cmid: cmid,
                    task_ids: taskIds,
                    sesskey: sesskey
                },
                traditional: false,
                dataType: 'json',
                success: function (response) {
                    loader.hide();
                    button.prop('disabled', false);

                    if (response.success) {
                        // Display chart
                        result.show();
                        displayChart(response);
                    } else {
                        showNotification(response.error || 'Error analyzing contest difficulty', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    loader.hide();
                    button.prop('disabled', false);
                    showNotification('Error loading analysis: ' + error, 'error');
                }
            });
        });

        // Export update function to global scope for use by manage_tasks.js
        window.bacsUpdateDifficultyChart = function () {
            console.log('window.bacsUpdateDifficultyChart called');
            updateChart(true); // Silent update (no loader/errors)
        };

        // Also make it available immediately
        console.log('bacs difficulty_analysis module initialized, cmid:', cmid);
    };

})();

