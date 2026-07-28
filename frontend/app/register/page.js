"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useAuth } from "../lib/AuthContext";

const EMPTY_FORM = { name: "", email: "", password: "", password_confirmation: "" };

export default function RegisterPage() {
  const { register } = useAuth();
  const router = useRouter();
  const [form, setForm] = useState(EMPTY_FORM);
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (e) => setForm((f) => ({ ...f, [e.target.name]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setFieldErrors({});
    setSubmitting(true);
    try {
      await register(form);
      router.push("/");
    } catch (err) {
      setError(err.message);
      setFieldErrors(err.errors || {});
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="container py-5" style={{ maxWidth: 420 }}>
      <h1 className="h3 fw-bold text-center mb-4">Create an Account</h1>

      {error && (
        <div className="alert alert-danger py-2" role="alert">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} noValidate>
        <div className="mb-3">
          <label htmlFor="name" className="form-label">
            Name
          </label>
          <input
            id="name"
            name="name"
            type="text"
            className={`form-control ${fieldErrors.name ? "is-invalid" : ""}`}
            value={form.name}
            onChange={handleChange}
            required
            autoComplete="name"
          />
          {fieldErrors.name && <div className="invalid-feedback">{fieldErrors.name[0]}</div>}
        </div>
        <div className="mb-3">
          <label htmlFor="email" className="form-label">
            Email
          </label>
          <input
            id="email"
            name="email"
            type="email"
            className={`form-control ${fieldErrors.email ? "is-invalid" : ""}`}
            value={form.email}
            onChange={handleChange}
            required
            autoComplete="email"
          />
          {fieldErrors.email && <div className="invalid-feedback">{fieldErrors.email[0]}</div>}
        </div>
        <div className="mb-3">
          <label htmlFor="password" className="form-label">
            Password
          </label>
          <input
            id="password"
            name="password"
            type="password"
            className={`form-control ${fieldErrors.password ? "is-invalid" : ""}`}
            value={form.password}
            onChange={handleChange}
            required
            autoComplete="new-password"
          />
          {fieldErrors.password && <div className="invalid-feedback">{fieldErrors.password[0]}</div>}
        </div>
        <div className="mb-3">
          <label htmlFor="password_confirmation" className="form-label">
            Confirm Password
          </label>
          <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            className="form-control"
            value={form.password_confirmation}
            onChange={handleChange}
            required
            autoComplete="new-password"
          />
        </div>
        <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
          {submitting ? "Creating account..." : "Sign Up"}
        </button>
      </form>

      <p className="text-center text-secondary mt-4 mb-0">
        Already have an account? <Link href="/login">Login</Link>
      </p>
    </div>
  );
}
