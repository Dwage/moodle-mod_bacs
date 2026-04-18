/* eslint-disable no-console */
/* global process */
require('dotenv').config();
const express = require('express');
const http = require('http');
const {Server} = require('socket.io');
const jwt = require('jsonwebtoken');
const cors = require('cors');

/**
 * Validate critical environment variables before starting the service.
 */
if (!process.env.BACS_WS_SECRET) {
    console.error('[CRITICAL] BACS_WS_SECRET is missing. Check your .env file.');
    process.exit(1);
}

if (!process.env.MOODLE_URL) {
    console.error('[CRITICAL] MOODLE_URL is missing. Check your .env file.');
    process.exit(1);
}

const moodleBaseUrl = (process.env.MOODLE_URL || '').replace(/\/+$/, '');

const CONFIG = {
    port: process.env.PORT || 3000,
    secret: process.env.BACS_WS_SECRET,
    moodleUrl: `${moodleBaseUrl}/mod/bacs/ajax_check_sybon.php`,
    origin: process.env.ALLOWED_ORIGIN || "*",
    interval: parseInt(process.env.WORKER_INTERVAL, 10) || 2000
};


const app = express();
app.use(cors({origin: CONFIG.origin}));
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: CONFIG.origin,
        methods: ["GET", "POST"]
    }
});

const activeWatches = new Set();

/**
 * Authenticate incoming socket connections using a JWT provided by the Moodle frontend.
 */
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    if (!token) {
        return next(new Error('Authentication failed: No token provided'));
    }

    jwt.verify(token, CONFIG.secret, (err, decoded) => {
        if (err) {
            return next(new Error('Authentication failed: Invalid token'));
        }
        socket.userId = decoded.user_id;
        next();
    });
});

io.on('connection', (socket) => {
    // Isolate users into specific rooms to ensure private data delivery.
    socket.join(`user_room_${socket.userId}`);

    /**
     * Registers a list of submission IDs to be monitored for status changes.
     */
    socket.on('watch_submits', (submitIds) => {
        if (Array.isArray(submitIds)) {
            submitIds.forEach(id => activeWatches.add(id));
            console.log(`[Socket] User ${socket.userId} monitoring IDs:`, submitIds);
        }
    });
});

/**
 * Background worker that synchronizes submission statuses with Moodle.
 * Polls Moodle for updates on all active IDs and notifies relevant users.
 */
setInterval(async() => {
    if (activeWatches.size === 0) {
 return;
}

    try {
        const response = await fetch(CONFIG.moodleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Auth-Secret': CONFIG.secret
            },
            body: JSON.stringify({submit_ids: Array.from(activeWatches)})
        });

        if (!response.ok) {
            throw new Error(`Moodle API responded with status ${response.status}`);
        }

        const data = await response.json();

        if (data.status === 'ok' && data.updated_submits?.length > 0) {
            data.updated_submits.forEach(submit => {
                // Emit the update only to the specific room belonging to the submit owner.
                io.to(`user_room_${submit.user_id}`).emit('submit_update', submit);

                activeWatches.delete(submit.submit_id);
                console.log(`[Worker] Status updated and removed for ID: #${submit.submit_id}`);
            });
        }
    } catch (error) {
        console.error('[Worker Error] Synchronization failed:', error.message);
    }
}, CONFIG.interval);

server.listen(CONFIG.port, () => {
    console.log('------------------------------------------------');
    console.log(`BACS WS Broker Service running on port ${CONFIG.port}`);
    console.log(`Environment: ${CONFIG.origin === '*' ? 'Development' : 'Restricted'}`);
    console.log('------------------------------------------------');
});