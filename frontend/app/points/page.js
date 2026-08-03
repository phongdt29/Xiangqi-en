"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { useAuth } from "../lib/AuthContext";
import { capturePointsOrder, createPointsOrder, listPackages, requestPayout } from "../lib/points-api";

const PAYPAL_CLIENT_ID = process.env.NEXT_PUBLIC_PAYPAL_CLIENT_ID;

const PACKAGE_LABELS = {
  basic: "Basic",
  plus: "Plus",
  pro: "Pro",
};

function loadPayPalSdk() {
  if (window.paypal) return Promise.resolve(window.paypal);

  return new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(PAYPAL_CLIENT_ID)}&currency=USD`;
    script.onload = () => resolve(window.paypal);
    script.onerror = () => reject(new Error("Could not load the PayPal checkout button."));
    document.body.appendChild(script);
  });
}

export default function PointsPage() {
  const { user, token, loading: authLoading, refreshUser } = useAuth();
  const [packages, setPackages] = useState(null);
  const [withdrawRate, setWithdrawRate] = useState(null);
  const [withdrawMinimum, setWithdrawMinimum] = useState(null);
  const [selected, setSelected] = useState(null);
  const [error, setError] = useState(null);
  const [status, setStatus] = useState(null);
  const buttonsContainerRef = useRef(null);
  const buttonsInstanceRef = useRef(null);

  const [withdrawPoints, setWithdrawPoints] = useState("");
  const [withdrawEmail, setWithdrawEmail] = useState("");
  const [withdrawPassword, setWithdrawPassword] = useState("");
  const [withdrawing, setWithdrawing] = useState(false);
  const [withdrawError, setWithdrawError] = useState(null);
  const [withdrawStatus, setWithdrawStatus] = useState(null);

  useEffect(() => {
    if (!token) return undefined;
    let cancelled = false;
    listPackages(token)
      .then((res) => {
        if (cancelled) return;
        setPackages(res.packages);
        setSelected((current) => current ?? Object.keys(res.packages)[0]);
        setWithdrawRate(res.withdrawRate);
        setWithdrawMinimum(res.withdrawMinimum);
      })
      .catch((e) => {
        if (!cancelled) setError(e.message);
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  const handleWithdraw = async (e) => {
    e.preventDefault();
    setWithdrawing(true);
    setWithdrawError(null);
    setWithdrawStatus(null);
    try {
      const res = await requestPayout(token, Number(withdrawPoints), withdrawEmail, withdrawPassword);
      await refreshUser();
      setWithdrawStatus(`Sent $${res.payout.amount_usd} to ${withdrawEmail}.`);
      setWithdrawPoints("");
      setWithdrawPassword("");
    } catch (err) {
      setWithdrawError(err.message);
    } finally {
      setWithdrawing(false);
    }
  };

  // Renders (or re-renders, on package change) the PayPal Buttons widget.
  // createOrder/onApprove always read `selected`/`token` fresh via closures
  // captured at render time, which is why the whole effect re-runs on change
  // instead of trying to update the button instance in place.
  useEffect(() => {
    if (!token || !selected || !PAYPAL_CLIENT_ID) return undefined;
    let cancelled = false;

    loadPayPalSdk()
      .then((paypal) => {
        if (cancelled || !buttonsContainerRef.current) return;

        buttonsInstanceRef.current?.close();
        buttonsContainerRef.current.innerHTML = "";

        const buttons = paypal.Buttons({
          createOrder: async () => {
            setError(null);
            const res = await createPointsOrder(token, selected);
            return res.orderId;
          },
          onApprove: async (data) => {
            setStatus("Confirming payment...");
            try {
              const res = await capturePointsOrder(token, data.orderID);
              await refreshUser();
              setStatus(`Success! +${res.pointsAdded} points added.`);
            } catch (e) {
              setStatus(null);
              setError(e.message);
            }
          },
          onError: (err) => {
            setError(err.message || "PayPal checkout failed.");
          },
        });

        buttons.render(buttonsContainerRef.current);
        buttonsInstanceRef.current = buttons;
      })
      .catch((e) => setError(e.message));

    return () => {
      cancelled = true;
    };
  }, [token, selected, refreshUser]);

  if (!authLoading && !user) {
    return (
      <div className="container py-5 text-center">
        <h1 className="h3 fw-bold mb-3">💰 Top Up Points</h1>
        <p className="text-secondary mb-4">You need an account to top up points.</p>
        <Link href="/login" className="btn btn-primary">
          Login
        </Link>
      </div>
    );
  }

  if (!user) return null;

  return (
    <div className="container py-5" style={{ maxWidth: 720 }}>
      <header className="text-center mb-4">
        <h1 className="h3 fw-bold mb-1">💰 Top Up Points</h1>
        <p className="text-secondary mb-0">
          Current balance: <span className="fw-semibold text-dark">{user.points}</span> points
        </p>
      </header>

      {!PAYPAL_CLIENT_ID && (
        <div className="alert alert-warning" role="alert">
          PayPal is not configured yet (missing <code>NEXT_PUBLIC_PAYPAL_CLIENT_ID</code>).
        </div>
      )}

      {error && (
        <div className="alert alert-danger" role="alert">
          {error}
        </div>
      )}

      {status && (
        <div className="alert alert-success" role="alert">
          {status}
        </div>
      )}

      {!packages && !error && <p className="text-secondary text-center">Loading packages...</p>}

      {packages && (
        <>
          <div className="row row-cols-1 row-cols-sm-3 g-3 mb-4">
            {Object.entries(packages).map(([key, pkg]) => (
              <div className="col" key={key}>
                <button
                  type="button"
                  className={`card h-100 w-100 border-2 text-start ${selected === key ? "border-primary" : ""}`}
                  onClick={() => setSelected(key)}
                >
                  <div className="card-body">
                    <div className="fw-bold">{PACKAGE_LABELS[key] ?? key}</div>
                    <div className="display-6 fw-bold text-primary">${pkg.usd.toFixed(2)}</div>
                    <div className="text-secondary small">{pkg.points} points</div>
                  </div>
                </button>
              </div>
            ))}
          </div>

          <div ref={buttonsContainerRef} />
        </>
      )}

      <hr className="my-5" />

      <header className="text-center mb-4">
        <h2 className="h4 fw-bold mb-1">💸 Withdraw to PayPal</h2>
        {withdrawRate && withdrawMinimum && (
          <p className="text-secondary mb-0 small">
            {withdrawRate} points = $1.00 - minimum withdrawal is {withdrawMinimum} points ($
            {(withdrawMinimum / withdrawRate).toFixed(2)}).
          </p>
        )}
      </header>

      {withdrawError && (
        <div className="alert alert-danger" role="alert">
          {withdrawError}
        </div>
      )}

      {withdrawStatus && (
        <div className="alert alert-success" role="alert">
          {withdrawStatus}
        </div>
      )}

      <form onSubmit={handleWithdraw} className="row g-2 justify-content-center">
        <div className="col-12 col-sm-4">
          <input
            type="number"
            min={withdrawMinimum ?? 0}
            max={user.points}
            step="1"
            className="form-control"
            placeholder="Points to withdraw"
            value={withdrawPoints}
            onChange={(e) => setWithdrawPoints(e.target.value)}
            required
          />
        </div>
        <div className="col-12 col-sm-5">
          <input
            type="email"
            className="form-control"
            placeholder="Your PayPal email"
            value={withdrawEmail}
            onChange={(e) => setWithdrawEmail(e.target.value)}
            required
          />
        </div>
        <div className="col-12 col-sm-3">
          <button type="submit" className="btn btn-outline-primary w-100" disabled={withdrawing}>
            {withdrawing ? "Sending..." : "Withdraw"}
          </button>
        </div>
        <div className="col-12 col-sm-9 mx-auto">
          <input
            type="password"
            className="form-control"
            placeholder="Confirm your account password"
            value={withdrawPassword}
            onChange={(e) => setWithdrawPassword(e.target.value)}
            autoComplete="current-password"
            required
          />
        </div>
      </form>
      <p className="text-secondary small text-center mt-2">
        {withdrawPoints > 0 && withdrawRate
          ? `≈ $${(Number(withdrawPoints) / withdrawRate).toFixed(2)} will be sent.`
          : " "}
      </p>
    </div>
  );
}
