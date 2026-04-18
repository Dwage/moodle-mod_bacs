# BACS contests

This is Moodle activity plugin for automatic solution judgment for programming tasks and for integrating programming contests into Moodle courses.

### Installation:

1. Copy the module code directly to the *moodleroot/mod/bacs* directory.
2. Go to http://your-moodle/admin (Site administration -> Notifications) to trigger the installation process.
3. Replace NoDefaultKey with your Sybon API key to access the tasks collections. If necessary, configure other default settings.
4. Use in any course as wished.

### Setting up the WebSocket Server (Node.js):

To enable real-time updates for submissions without page reloads, you need to configure and run the included Node.js WebSocket broker.

1. Make sure you have **Node.js** and **npm** installed on your server.
2. Navigate to the WebSocket broker directory and install the required dependencies:
   ```bash
   cd /path/to/your/moodle/mod/bacs/ws-broker
   npm install
   ```
3. Retrieve your **WebSocket Secret Key**:
   Go to your Moodle settings: **Site administration -> Plugins -> Activity modules -> BACS contests**. Moodle automatically generates a secure cryptographic key during installation. Copy the value from the **WebSocket Secret Key** field.
4. Configure the environment variables:
   In the `ws-broker` directory, create a `.env` file (you can copy `.env.example`) and configure it.
   Add the following configuration, pasting your copied key and your actual Moodle URL:
   ```env
   PORT=3000
   BACS_WS_SECRET=your_copied_secret_key_here
   MOODLE_URL=http://localhost:8000
   ALLOWED_ORIGIN=*
   WORKER_INTERVAL=2000
   ```
5. Start the server:
   For development/testing:
   ```bash
   node server.js
   ```
   *Note for production:* It is highly recommended to run the WebSocket server using a process manager like **PM2** and set up a reverse proxy (Nginx/Apache) to handle secure SSL/WSS connections:
   ```bash
   npm install pm2 -g
   pm2 start server.js --name "bacs-ws-broker"
   pm2 save
   pm2 startup
   ```
6. Finally, return to the Moodle settings page and fill in the **WebSocket URL (Public)** field with your broker's URL (e.g., `http://your-server-ip:3000` or `wss://your-domain.com/ws`).

### Creating contest:

1. Open your Moodle course page.
2. Turn on an Edit mode.
3. Click "Add an activity or resource" on your section.
4. Choose BACS contests and click "Add".
5. Enter a name for the new contest.
6. Select tasks for your contest.
7. Click one of the save buttons to complete the creation of the contest.

Check [contest settings](mds/Contest%20Settings.md) file for more information.

### Plugin Features:

- Sending solutions and viewing their results, including execution time and memory consumption on all tests, as well as input/output data on pretests (open to test participants).
- The contest monitor is a summary table of the results of all participants on all tasks of the contest.
- Time limits for contests, upsolving and presolving settings.
- Several contest evaluation systems: IOI, ICPC, General.
- Score settings for each task in the contest (for scoring systems that work with scores).
- The ability to change the evaluation system of the contest, the set of tasks of the contest, the order of the tasks of the contest, as well as the scores for each of the tasks, even at any time. Even during the contest or after its completion.
- Support for groups of students.
- Virtual contests that allow each student to participate in the contest at an independent time.
- The ability to double-check, recalculate points, reject and change the results of parcels.
- Analytics: Watch participants' progress, rank changes, and task statistics via intelligent dynamic charts.

### Solving problems in different programming languages:

Examples of solving the A+B problem in all available languages can be found [here](mds/Sample%20Solutions.md)