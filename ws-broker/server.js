/* eslint-disable no-console */
const express = require('express');
const http = require('http');
const {Server} = require('socket.io');
const jwt = require('jsonwebtoken');
const cors = require('cors');

const SHARED_SECRET = 'super-secret-bacs-key-2024';
const MOODLE_API_URL = 'http://localhost:8000/mod/bacs/ajax_check_sybon.php';

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {cors: {origin: "*", methods: ["GET", "POST"]}});

const activeWatches = new Set();

io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    if (!token) {
 return next(new Error('No token provided'));
}
    jwt.verify(token, SHARED_SECRET, (err, decoded) => {
        if (err) {
 return next(new Error('Invalid token'));
}
        socket.userId = decoded.user_id;
        next();
    });
});

io.on('connection', (socket) => {
    socket.join(`user_room_${socket.userId}`);

    socket.on('watch_submits', (submitIds) => {
        if (Array.isArray(submitIds)) {
            submitIds.forEach(id => activeWatches.add(id));
            console.log(`[WS] Начали отслеживать посылки:`, submitIds);
        }
    });
});

setInterval(async() => {
    if (activeWatches.size === 0) {
 return;
}

    try {
        const response = await fetch(MOODLE_API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Auth-Secret': SHARED_SECRET},
            body: JSON.stringify({submit_ids: Array.from(activeWatches)})
        });

        const data = await response.json();

        if (data.status === 'ok' && data.updated_submits.length > 0) {
            data.updated_submits.forEach(submit => {
                io.to(`user_room_${submit.user_id}`).emit('submit_update', submit);

                activeWatches.delete(submit.submit_id);
                console.log(`[Worker] Посылка #${submit.submit_id} проверена и удалена из отслеживания.`);
            });
        }
    } catch (error) {
        console.error('[Worker Error]', error.message);
    }
}, 2000);

server.listen(3000, () => {
    console.log('WS Server запущен на порту 3000. Воркер спит, пока нет серых посылок.');
});