const net = require('node:net');
const tls = require('node:tls');
const fs = require('node:fs');
const udp = require('node:dgram');

const port = 1333;
const tlsPort = 1555;
const udpPort = 1777;

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
tlsServer.listen(tlsPort, '127.0.0.1');

const tcpServer = net.createServer({}, (socket) => {
    const addr = socket.remoteAddress + ':' + socket.remotePort;
    process.stdout.write('connected ' + addr + "\n");
    socket.on('data', () => {
        socket.write("pong\n");
        socket.destroy();
    });
    socket.on('close', () => process.stdout.write('closed ' + addr + "\n"));
});
tcpServer.on('listening', () => process.stdout.write("TCP listening on " + port + "\n"));
tcpServer.on('error', (err) => {
    throw err;
});
tcpServer.listen(port, '127.0.0.1');

const udpServer = udp.createSocket('udp4');
udpServer.on('message', (msg, rinfo) => {
    process.stdout.write("Datagram arrived: " + msg.toString('utf-8') + "\n")
    udpServer.send('pong', rinfo.port, rinfo.address, (error) => {
        if (error) {
            throw error;
        }
    });
    udpServer.send('pong', rinfo.port, rinfo.address, (error) => {
        if (error) {
            throw error;
        }
    });
});

udpServer.on('connect', () => console.log('connected'));

udpServer.on('listening', () => {
    process.stdout.write("UDP listening on " + udpServer.address().port + "\n")
})
udpServer.on('error', (err) => {
    throw err;
});
udpServer.bind(udpPort, '127.0.0.1');
