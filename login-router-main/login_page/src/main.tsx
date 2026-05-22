import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import ErrorBoundary from "./components/ErrorBoundary";
import "./styles.css";

/* 🔥 Bootstrap CSS */
import "bootstrap/dist/css/bootstrap.min.css";

/* 🔥 Bootstrap Icons (optional but recommended) */
import "bootstrap-icons/font/bootstrap-icons.css";

const rootElement = document.getElementById("root");

if (!rootElement) {
  throw new Error("React root element was not found.");
}

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <ErrorBoundary>
      <App />
    </ErrorBoundary>
  </React.StrictMode>
);
