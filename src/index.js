import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import App, { ErrorBoundary } from "./App";

const container = document.getElementById("root");
if (!container) {
  // Render an inline message instead of crashing if the mount node is missing.
  document.body.innerHTML =
    '<div style="font-family:sans-serif;text-align:center;padding:2rem">' +
    "<h2>Application failed to start</h2>" +
    "<p>Root mount element not found.</p></div>";
} else {
  const root = ReactDOM.createRoot(container);
  root.render(
    <React.StrictMode>
      <ErrorBoundary>
        <App />
      </ErrorBoundary>
    </React.StrictMode>
  );
}
