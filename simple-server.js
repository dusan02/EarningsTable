// Thin entry point. The server implementation lives in ./server.
// Kept as the entry so existing tooling (package.json scripts, PM2 ecosystem,
// systemd unit) that references simple-server.js keeps working unchanged.
require("./server");
