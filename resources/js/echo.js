import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const reverbConfig = window.reverb ?? {};

window.Echo = new Echo({
    broadcaster: "reverb",
    key: reverbConfig.key,
    wsHost: reverbConfig.host,
    wsPort: reverbConfig.port ?? 80,
    wssPort: reverbConfig.port ?? 443,
    forceTLS: (reverbConfig.scheme ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});
