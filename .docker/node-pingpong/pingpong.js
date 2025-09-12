const net = require("net");
const dgram = require("dgram");

const HOST = "0.0.0.0";
const TCP_PORT = 4000;
const UDP_PORT = 4001;

const tcpServer = net.createServer((socket) => {
  console.log(`New TCP connection from ${socket.remoteAddress}:${socket.remotePort}`);

  socket.on("data", (data) => {
    const msg = data.toString().trim();
    console.log(`[TCP] Received from ${socket.remoteAddress}:${socket.remotePort} -> "${msg}"`);

    if (msg === "ping") {
      socket.write("pong\n");
      console.log(`[TCP] Replied to ${socket.remoteAddress}:${socket.remotePort} with "pong"`);
    }
  });

  socket.on("close", () => {
    console.log(`TCP connection closed: ${socket.remoteAddress}:${socket.remotePort}`);
  });
});

tcpServer.listen(TCP_PORT, HOST, () => {
  console.log(`TCP server listening on ${HOST}:${TCP_PORT}`);
});

const udpServer = dgram.createSocket("udp4");

udpServer.on("message", (msg, rinfo) => {
  const message = msg.toString().trim();
  console.log(`[UDP] Received from ${rinfo.address}:${rinfo.port} -> "${message}"`);

  if (message === "ping") {
    udpServer.send("pong", rinfo.port, rinfo.address, (err) => {
      if (err) {
        console.error(`[UDP] Error replying to ${rinfo.address}:${rinfo.port}`, err);
      } else {
        console.log(`[UDP] Replied to ${rinfo.address}:${rinfo.port} with "pong"`);
      }
    });
  }
});

udpServer.bind(UDP_PORT, HOST, () => {
  console.log(`UDP server listening on ${HOST}:${UDP_PORT}`);
});
