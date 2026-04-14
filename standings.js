/* jshint ignore:start */
/* eslint-disable */
class StandingsStudent {
  constructor(student, standings) {
    this.student = student;
    this.standings = standings;
    this.rank_position = 42;

    console.log(this.student);

    this.submits = [];
  }

  get firstname() {
    return this.student.firstname;
  }

  get lastname() {
    return this.student.lastname;
  }

  get fullname() {
    return this.student.firstname + ' ' + this.student.lastname;
  }

  get user_id() {
    return this.student.id;
  }

  get start_time_cut() {
    return this.student.starttime;
  }

  get end_time_cut() {
    return this.student.endtime;
  }

  get tasks() {
    return this.standings.tasks;
  }

  add_submit(submit) {
    this.submits.push(submit);
  }

  build_results() {
    this.active = false;

    this.results = this.tasks.map((task) => ({
      task,
      accepted: false,
      is_first_accepted: false,
      judged: true,
      attempts: 0,
      attempts_upto_best: 0,
      points: 0,
      best_submit_time: 0,
      last_submit_time: 0,
      penalty_time: 0,
      incident_level: 0,
    }));

    for (const submit of this.submits) {
      if (!submit.task) continue;
      if (this.standings.hide_upsolving && this.end_time_cut <= submit.submit_time) continue;

      this.active = true;

      const idx = submit.task.task_order - 1;
      const result = this.results[idx];

      result.attempts++;
      result.last_submit_time = Math.max(0, Math.floor((submit.submit_time - this.start_time_cut) / 60));
      result.incident_level = Math.max(result.incident_level, submit.incident_level);

      const is_improvement =
        this.standings.mode === StandingsRenderer.MODE_ICPC
          ? submit.accepted && !result.accepted
          : result.points < submit.points;

      if (is_improvement) {
        result.points = submit.points;
        result.attempts_upto_best = result.attempts;
        result.best_submit_time = result.last_submit_time;

        const prev_attempts = result.attempts_upto_best - 1;
        result.penalty_time = result.best_submit_time + 20 * prev_attempts;
      }

      result.accepted = result.accepted || submit.accepted;
      result.is_first_accepted = result.is_first_accepted || submit.is_first_accepted;
      result.judged = result.judged && submit.judged;
    }

    this.total = {
      solved: 0,
      tried: 0,
      failed: 0,
      points: 0,
      penalty_time: 0,
      last_improvement_time: 0,
    };

    for (const result of this.results) {
      if (result.accepted) {
        this.total.solved++;
      }

      if (result.attempts > 0) this.total.tried++;

      if (result.points > 0) {
        this.total.points += result.points;
        this.total.penalty_time += result.penalty_time;

        this.total.last_improvement_time = Math.max(this.total.last_improvement_time, result.last_submit_time);
      }
    }

    this.total.failed = this.total.tried - this.total.solved;
  }
}

class StandingsRenderer {
  constructor(standings) {
    this.standings = standings;
  }

  get mode() {
    return this.standings.mode;
  }

  get show_first_accepted_flag() {
    return this.standings.show_first_accepted_flag;
  }

  get show_incident_flags() {
    return this.standings.show_incident_flags;
  }

  get show_testing_flag() {
    return this.standings.show_testing_flag;
  }

  get show_submits_upto_best() {
    return this.standings.show_submits_upto_best;
  }

  get show_last_improvement_column() {
    return this.standings.show_last_improvement_column;
  }

  static get MODE_IOI() {
    return 0;
  }
  static get MODE_ICPC() {
    return 1;
  }
  static get MODE_GENERAL() {
    return 2;
  }

  get_string(str, fallback = '') {
    return this.standings.get_string(str, fallback);
  }

  get students_strict_comparator() {
    const loose_comparator = this.students_loose_comparator;

    return (a, b) => {
      const result = loose_comparator(a, b);

      if (result !== 0) return result;

      return a.fullname.localeCompare(b.fullname);
    };
  }

  get students_loose_comparator() {
    if (this.mode === StandingsRenderer.MODE_IOI) {
      return (a, b) => {
        if (a.total.points > b.total.points) return -1;
        if (a.total.points < b.total.points) return 1;

        return 0;
      };
    }

    if (this.mode === StandingsRenderer.MODE_ICPC) {
      return (a, b) => {
        if (a.total.solved > b.total.solved) return -1;
        if (a.total.solved < b.total.solved) return 1;

        if (a.total.penalty_time < b.total.penalty_time) return -1;
        if (a.total.penalty_time > b.total.penalty_time) return 1;

        if (a.total.last_improvement_time < b.total.last_improvement_time) return -1;
        if (a.total.last_improvement_time > b.total.last_improvement_time) return 1;

        return 0;
      };
    }

    if (this.mode === StandingsRenderer.MODE_GENERAL) {
      return (a, b) => {
        if (a.total.points > b.total.points) return -1;
        if (a.total.points < b.total.points) return 1;

        if (a.total.solved > b.total.solved) return -1;
        if (a.total.solved < b.total.solved) return 1;

        if (a.total.penalty_time < b.total.penalty_time) return -1;
        if (a.total.penalty_time > b.total.penalty_time) return 1;

        if (a.total.last_improvement_time < b.total.last_improvement_time) return -1;
        if (a.total.last_improvement_time > b.total.last_improvement_time) return 1;

        return 0;
      };
    }

    throw new Error('Invalid standings rendering mode');
  }

  get show_total_penalty_time_column() {
    if (this.mode == StandingsRenderer.MODE_IOI) return false;
    if (this.mode == StandingsRenderer.MODE_ICPC) return true;
    if (this.mode == StandingsRenderer.MODE_GENERAL) return true;

    throw new Error('Invalid standings rendering mode');
  }

  get show_total_points_column() {
    if (this.mode == StandingsRenderer.MODE_IOI) return true;
    if (this.mode == StandingsRenderer.MODE_ICPC) return false;
    if (this.mode == StandingsRenderer.MODE_GENERAL) return true;

    throw new Error('Invalid standings rendering mode');
  }

  with_font_color(inner_html, result) {
    const font_color = result.accepted ? 'green' : 'red';
    return `<font color="${font_color}">${inner_html}</font>`;
  }

  with_smaller_font(inner_html) {
    return `<span class="standings-cell-small-text">${inner_html}</span>`;
  }

  with_results_link(inner_html, result, student) {
    const href = `results.php?id=${this.standings.course_module_id}&user_id=${student.user_id}&task_id=${result.task.task_id}`;
    return `<a href="${href}">${inner_html}</a>`;
  }

  format_as_contest_time(duration) {
    const minutes = duration % 60;
    const hours = Math.floor(duration / 60);

    return `${hours}:${Math.floor(minutes / 10)}${minutes % 10}`;
  }

  part_points(result) {
    return `${result.points}`;
  }

  part_icpc_main(result) {
    const failed_attempts = result.accepted ? result.attempts_upto_best - 1 : result.attempts;
    const sign = result.accepted ? '+' : '-';

    return `${sign}${failed_attempts > 0 ? failed_attempts : ''}`;
  }

  part_submits_count(result) {
    return this.show_submits_upto_best ? `[${result.attempts_upto_best}/${result.attempts}]` : `[${result.attempts}]`;
  }

  part_penalty_time(result) {
    const duration = result.points > 0 ? result.best_submit_time : result.last_submit_time;
    return this.format_as_contest_time(duration);
  }

  part_testing_flag(result) {
    return !this.show_testing_flag || result.accepted || result.judged ? '' : '<sup>?</sup>';
  }

  part_first_accepted_flag_style_class(result) {
    return this.show_first_accepted_flag && result.is_first_accepted ? 'standings-cell-first-accepted' : '';
  }

  part_incident_warning_flag_style_class(result) {
    return this.show_incident_flags && result.incident_level >= 10 ? 'standings-cell-incident-warning' : '';
  }

  part_incident_critical_flag_style_class(result) {
    return this.show_incident_flags && result.incident_level >= 20 ? 'standings-cell-incident-critical' : '';
  }

  render_result_ioi(result, student, can_view) {
    if (result.attempts === 0) return '-';

    let cell_html = `${this.part_points(result)}${this.part_testing_flag(result)}`;

    let submits_html = `<sub>${this.part_submits_count(result)}</sub>`;
    if (can_view) submits_html = this.with_results_link(submits_html, result, student);

    cell_html += submits_html;

    return this.with_font_color(cell_html, result);
  }

  render_result_icpc(result, student, can_view) {
    if (result.attempts === 0) return '-';

    let up_html = `${this.part_icpc_main(result)}${this.part_testing_flag(result)}`;
    let down_html = this.part_penalty_time(result);

    if (can_view) down_html = this.with_results_link(down_html, result, student);

    const final_html = `${up_html}<br>${this.with_smaller_font(down_html)}`;

    return this.with_font_color(final_html, result);
  }

  render_result_general(result, student, can_view) {
    if (result.attempts === 0) return '-';

    const up_html = `${this.part_points(result)}${this.part_testing_flag(result)}`;
    let down_html = `${this.part_penalty_time(result)}<br>${this.part_submits_count(result)}`;

    if (can_view) {
      down_html = this.with_results_link(down_html, result, student);
    }

    const final_html = `${up_html}<br>${this.with_smaller_font(down_html)}`;

    return this.with_font_color(final_html, result);
  }

  render_result_cell(result, student, can_view) {
    let inner_html = '';

    if (this.mode === StandingsRenderer.MODE_IOI) {
      inner_html = this.render_result_ioi(result, student, can_view);
    } else if (this.mode === StandingsRenderer.MODE_ICPC) {
      inner_html = this.render_result_icpc(result, student, can_view);
    } else if (this.mode === StandingsRenderer.MODE_GENERAL) {
      inner_html = this.render_result_general(result, student, can_view);
    } else {
      throw new Error('Invalid standings rendering mode');
    }

    const first_accepted_style = this.part_first_accepted_flag_style_class(result);
    const incident_warning_style = this.part_incident_warning_flag_style_class(result);
    const incident_critical_style = this.part_incident_critical_flag_style_class(result);

    return `
            <td class="text-center align-middle cell standings-cell ${first_accepted_style} ${incident_warning_style} ${incident_critical_style}" 
                title="${result.task.letter}. ${result.task.name}">
                ${inner_html}
            </td>
        `;
  }

  render_student(student) {
    if (this.standings.hide_inactive && !student.active) {
      return '';
    }

    const can_view = this.standings.has_capability_view_any || this.standings.moodle_user_id === student.user_id;
    let html = '<tr>';

    const rank_position_html =
      student.rank_min_position === student.rank_max_position
        ? student.rank_min_position
        : `${student.rank_min_position}-${student.rank_max_position}`;

    html += `<td class="cell text-nowrap text-center align-middle">${rank_position_html}</td>`;

    let can_view_link_html = '';
    if (can_view) {
      can_view_link_html = `
                <sub>
                    <a href="results.php?id=${this.standings.course_module_id}&user_id=${student.user_id}">
                        [${this.get_string('submitslowercase')}]
                    </a>
                </sub>`;
    }

    if (this.standings.split_fullname) {
      html += `<td class="cell align-middle text-nowrap" data-user-id="${student.user_id}">${student.firstname}</td>`;
      html += `<td class="cell align-middle text-nowrap" data-user-id="${student.user_id}">${student.lastname} ${can_view_link_html}</td>`;
    } else {
      html += `<td class="cell align-middle text-nowrap" data-user-id="${student.user_id}">${student.fullname} ${can_view_link_html}</td>`;
    }

    for (const result of student.results) {
      html += this.render_result_cell(result, student, can_view);
    }

    html += `<td class="cell text-center align-middle">
                    <font color="green">${student.total.solved}</font>`;
    if (student.total.failed > 0) {
      html += `/ <font color="red">${student.total.failed}</font>`;
    }
    html += '</td>';

    if (this.show_total_points_column) {
      html += `<td class="cell text-center align-middle">${student.total.points}</td>`;
    }

    if (this.show_total_penalty_time_column) {
      html += `<td class="cell text-center align-middle">${student.total.penalty_time}</td>`;
    }

    if (this.show_last_improvement_column) {
      html += `<td class="cell text-center align-middle">${this.format_as_contest_time(student.total.last_improvement_time)}</td>`;
    }

    html += '</tr>';

    return html;
  }

  render_header() {
    let html = `
            <tr>
                <th class="header text-center align-middle" scope="col">N</th>`;

    if (this.standings.split_fullname) {
      html += `<th class="header align-middle" scope="col">${this.get_string('firstname', 'First Name')}</th>`;
      html += `<th class="header align-middle" scope="col">${this.get_string('lastname', 'Last Name')}</th>`;
    } else {
      html += `<th class="header align-middle" scope="col">${this.get_string('username')}</th>`;
    }

    for (const task of this.standings.tasks) {
      html += `
                <th class="header text-center align-middle" scope="col" title="${task.letter}. ${task.name}">
                    ${task.letter}
                </th>
            `;
    }

    html += '<th class="header text-center align-middle" scope="col">+</th>';

    if (this.show_total_points_column) {
      html += `<th class="header text-center align-middle" scope="col">${this.get_string('points')}</th>`;
    }

    if (this.show_total_penalty_time_column) {
      html += `<th class="header text-center align-middle" scope="col">${this.get_string('penalty')}</th>`;
    }

    if (this.show_last_improvement_column) {
      html += `<th class="header text-center align-middle" scope="col">${this.get_string('lastimprovedat')}</th>`;
    }

    html += '</tr>';

    return html;
  }
}

class Standings {
  constructor(
    students,
    tasks,
    submits,
    course_module_id,
    moodle_user_id,
    contest_starttime,
    contest_endtime,
    mode,
    hide_upsolving,
    hide_inactive,
    has_capability_view_any,
    localized_strings,
    incidents_info,
    split_fullname,
  ) {
    students = students.map((student) => new StandingsStudent(student, this));

    // set up params
    this.students = students;
    this.tasks = tasks;
    this.submits = submits;
    this.course_module_id = course_module_id;
    this.moodle_user_id = moodle_user_id;
    this.contest_starttime = contest_starttime;
    this.contest_endtime = contest_endtime;
    this.mode = mode;
    this.hide_upsolving = hide_upsolving;
    this.hide_inactive = hide_inactive;
    this.has_capability_view_any = has_capability_view_any;
    this.localized_strings = localized_strings;

    this.split_fullname = split_fullname || false;

    // set up default params
    this.show_first_accepted_flag = true;
    this.show_incident_flags = false;
    this.show_testing_flag = true;
    this.show_submits_upto_best = false;
    this.show_last_improvement_column = false;

    // prepare indexes
    this.student_by_id = Object.fromEntries(students.map((student) => [student.user_id, student]));

    this.task_by_id = Object.fromEntries(tasks.map((task) => [task.task_id, task]));

    this.incident_level_by_submit_id = {};
    if (incidents_info) {
      incidents_info.forEach((incident) => {
        const incident_level = incident.method == 'tokenseq' ? 20 : 10;

        incident.submit_ids.forEach((submit_id) => {
          const old_incident_level = this.incident_level_by_submit_id[submit_id] || 0;

          this.incident_level_by_submit_id[submit_id] = Math.max(incident_level, old_incident_level);
        });
      });
    }

    // prepare virtual submit times
    submits.forEach((submit) => {
      if (!this.student_by_id.hasOwnProperty(submit.user_id)) return;

      const author = this.student_by_id[submit.user_id];
      submit.virtual_submit_time = submit.submit_time - author.start_time_cut;
    });

    // server is not guaranteed to give sorted submits array
    submits.sort((a, b) => {
      if (a.virtual_submit_time !== b.virtual_submit_time) {
        return a.virtual_submit_time - b.virtual_submit_time;
      }
      return a.id - b.id;
    });

    // prepare additional info
    const globally_solved_tasks = new Set();

    this.submits.forEach((submit) => {
      submit.accepted = submit.result_id == 13; // Accepted verdict
      submit.judged = submit.result_id != 1 /* Pending verdict */ && submit.result_id != 2 /* Running verdict */;
      submit.task = this.task_by_id[submit.task_id];
      submit.is_first_accepted = false;
      submit.incident_level = this.incident_level_by_submit_id[submit.id] || 0;

      submit.points = parseInt(submit.points, 10);

      if (submit.accepted && this.student_by_id[submit.user_id]) {
        submit.is_first_accepted = !globally_solved_tasks.has(submit.task_id);
        globally_solved_tasks.add(submit.task_id);
      }
    });

    console.log(submits);

    tasks.forEach((task) => {
      task.letter = String.fromCharCode('A'.charCodeAt(0) + Number(task.task_order) - 1);
    });

    // fill submits
    submits.forEach((submit) => {
      if (this.student_by_id.hasOwnProperty(submit.user_id)) {
        this.student_by_id[submit.user_id].add_submit(submit);
      }
    });

    this.build();
  }

  get_string(str, fallback = '') {
    const localized_result = this.localized_strings[str];

    if (!localized_result) {
      return fallback || `[[${str}]]`;
    }

    return localized_result;
  }

  toggle_upsolving() {
    this.hide_upsolving = !this.hide_upsolving;
    this.build();
    return this.hide_upsolving;
  }

  toggle_inactive() {
    this.hide_inactive = !this.hide_inactive;
    localStorage.setItem('standings_hide_inactive', this.hide_inactive);
    this.build();
    return this.hide_inactive;
  }

  toggle_split_fullname() {
    this.split_fullname = !this.split_fullname;
    localStorage.setItem('standings_split_fullname', this.split_fullname);
    this.build();
    return this.split_fullname;
  }

  toggle_show_first_accepted_flag() {
    this.show_first_accepted_flag = !this.show_first_accepted_flag;
    this.build();
    return this.show_first_accepted_flag;
  }

  toggle_show_incident_flags() {
    this.show_incident_flags = !this.show_incident_flags;
    this.build();
    return this.show_incident_flags;
  }

  toggle_show_testing_flag() {
    this.show_testing_flag = !this.show_testing_flag;
    this.build();
    return this.show_testing_flag;
  }

  toggle_show_submits_upto_best() {
    this.show_submits_upto_best = !this.show_submits_upto_best;
    this.build();
    return this.show_submits_upto_best;
  }

  toggle_show_last_improvement_column() {
    this.show_last_improvement_column = !this.show_last_improvement_column;
    this.build();
    return this.show_last_improvement_column;
  }

  set_mode(mode) {
    this.mode = Number(mode);
    this.build();
  }

  build() {
    var renderer = new StandingsRenderer(this);

    // build each line
    this.students.forEach((student) => {
      student.build_results();
    });

    // sort students
    this.students.sort(renderer.students_strict_comparator);

    // assign positions
    let curpos = 0;
    while (curpos < this.students.length) {
      let nextpos = curpos + 1;

      while (
        nextpos < this.students.length &&
        renderer.students_loose_comparator(this.students[curpos], this.students[nextpos]) === 0
      ) {
        nextpos++;
      }

      const min_position = curpos + 1;
      const max_position = nextpos;

      for (let i = curpos; i < nextpos; i++) {
        this.students[i].rank_min_position = min_position;
        this.students[i].rank_max_position = max_position;
      }

      curpos = nextpos;
    }

    // prepare html
    this.students.forEach((student) => {
      student.html = renderer.render_student(student);
    });

    // prepare stats
    this.task_stats = this.tasks.map((task) => ({
      task,
      solved: 0,
      tried: 0,
    }));

    this.tasks.forEach((task, i) => {
      this.students.forEach((student) => {
        if (student.results[i].accepted) this.task_stats[i].solved += 1;
        if (student.results[i].attempts > 0) this.task_stats[i].tried += 1;
      });
    });

    // build
    this.html = '';

    // header
    this.html += `<thead>${renderer.render_header()}</thead>`;

    this.html += '<tbody>';
    // rows
    this.students.forEach((student) => {
      this.html += student.html;
    });
    this.html += '</tbody>';

    // stats
    this.html += '<tfoot>';

    this.html += '<tr>' + '<td></td>';

    if (this.split_fullname) {
      this.html += '<td></td>';
    }

    this.html +=
      '<td>' +
      '<font color="green">' +
      this.get_string('amountofaccepted') +
      '</font>' +
      '</br>' +
      '<font color="grey">' +
      this.get_string('amountoftried') +
      '</font>' +
      '</td>';

    this.task_stats.forEach((task_stat) => {
      this.html += `
                    <td class="cell text-center align-middle">
                        <font color="green" title="${task_stat.task.letter}. ${task_stat.task.name}">
                            ${task_stat.solved}
                        </font>
                        <br>
                        <font color="grey" title="${task_stat.task.letter}. ${task_stat.task.name}">
                            ${task_stat.tried}
                        </font>
                    </td>
                `;
    });

    this.html += '<td></td>' + '</tr>' + '</tfoot>';

    // show
    document.getElementById('standings_table').innerHTML = this.html;
  }
}
