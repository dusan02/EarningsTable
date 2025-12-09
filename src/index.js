import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import App from "./App";

const container = document.getElementById("root");

if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
} else {
  // CRA dev server now powers a static dashboard where the DOM already exists,
  // so skip mounting React instead of throwing to keep the page usable.
  if (process.env.NODE_ENV !== "production") {
    console.warn(
      "React root element not found. Skipping React render (static dashboard mode)."
    );
  }
}
