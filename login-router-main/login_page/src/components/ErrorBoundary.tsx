import React from "react";

type ErrorBoundaryProps = {
  children: React.ReactNode;
};

type ErrorBoundaryState = {
  error: Error | null;
};

export default class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
  private readonly handleWindowError = (event: ErrorEvent) => {
    const nextError =
      event.error instanceof Error
        ? event.error
        : new Error(event.message || "Unexpected runtime error.");

    this.setState({ error: nextError });
  };

  private readonly handleUnhandledRejection = (event: PromiseRejectionEvent) => {
    const reason = event.reason;
    const nextError =
      reason instanceof Error
        ? reason
        : new Error(typeof reason === "string" ? reason : "Unhandled promise rejection.");

    this.setState({ error: nextError });
  };

  state: ErrorBoundaryState = {
    error: null,
  };

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { error };
  }

  componentDidMount() {
    window.addEventListener("error", this.handleWindowError);
    window.addEventListener("unhandledrejection", this.handleUnhandledRejection);
  }

  componentWillUnmount() {
    window.removeEventListener("error", this.handleWindowError);
    window.removeEventListener("unhandledrejection", this.handleUnhandledRejection);
  }

  componentDidCatch(error: Error) {
    console.error("Application render failed:", error);
  }

  private handleReset = () => {
    this.setState({ error: null });
    window.location.assign("/");
  };

  private handleClearSession = () => {
    try {
      localStorage.removeItem("auth");
      sessionStorage.removeItem("auth");
    } catch {
      // Ignore browsers/storage modes that block clearing.
    }

    this.setState({ error: null });
    window.location.assign("/login");
  };

  render() {
    if (!this.state.error) {
      return this.props.children;
    }

    return (
      <main className="app-error-shell">
        <section className="app-error-card">
          <p className="app-error-kicker">Application Error</p>
          <h1>The website hit a runtime problem.</h1>
          <p className="app-error-text">
            Blank screen ab nahi aani chahiye. Niche error message dikh raha hai, aur aap session
            clear karke login page par wapas ja sakte hain.
          </p>
          <pre className="app-error-details">{this.state.error.message || "Unknown application error"}</pre>
          <div className="app-error-actions">
            <button type="button" className="todoist-btn-primary" onClick={this.handleReset}>
              Reload Home
            </button>
            <button type="button" className="todoist-btn-ghost" onClick={this.handleClearSession}>
              Clear Local Session
            </button>
          </div>
        </section>
      </main>
    );
  }
}
