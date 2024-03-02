const net = require('node:net');
const tls = require('node:tls');
const fs = require('node:fs');

const port = 1333;
const tlsPort = 1555;

const tlsOpts = {
    key: fs.readFileSync('./test.key'),
    cert: fs.readFileSync('./test.crt'),
}

const tlsServer = tls.createServer(tlsOpts, (socket) => {
    const addr = socket.remoteAddress + ':' + socket.remotePort;
    process.stdout.write('TLS connected ' + addr + "\n");
    socket.on('data', (data) => {
        if (data.toString('utf-8') === "ping\n") {
            socket.end("pong\n");
        } else {
            socket.end();
        }
    });
    socket.on('close', () => process.stdout.write('TLS closed ' + addr + "\n"));
});
tlsServer.on('listening', () => process.stdout.write("TLS listening on " + tlsPort + "\n"));
tlsServer.on('error', (err) => {
    throw err;
});
tlsServer.listen(tlsPort, '0.0.0.0');

const server = net.createServer({}, (socket) => {
    const addr = socket.remoteAddress + ':' + socket.remotePort;
    process.stdout.write('connected ' + addr + "\n");
    socket.on('data', (data) => {
        if (data.toString('utf-8') === "ping\n") {
            socket.end("pong\n");
        } else {
            socket.end();
        }
    });
    socket.on('close', () => process.stdout.write('closed ' + addr + "\n"));
});
server.on('listening', () => process.stdout.write("listening on " + port + "\n"));
server.on('error', (err) => {
    throw err;
});
server.listen(port, '0.0.0.0');
