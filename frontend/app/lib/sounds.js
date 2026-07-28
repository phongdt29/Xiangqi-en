"use client";

const STORAGE_KEY = "xiangqi_sound_enabled";

let audioCtx;

function getContext() {
  if (typeof window === "undefined") return null;
  if (!audioCtx) {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    audioCtx = new Ctx();
  }
  if (audioCtx.state === "suspended") {
    audioCtx.resume();
  }
  return audioCtx;
}

export function isSoundEnabled() {
  if (typeof window === "undefined") return true;
  return window.localStorage.getItem(STORAGE_KEY) !== "off";
}

export function setSoundEnabled(enabled) {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(STORAGE_KEY, enabled ? "on" : "off");
}

function beep({ frequency, duration, type = "sine", volume = 0.15, delay = 0 }) {
  if (!isSoundEnabled()) return;
  const ctx = getContext();
  if (!ctx) return;

  const start = ctx.currentTime + delay;
  const oscillator = ctx.createOscillator();
  const gain = ctx.createGain();
  oscillator.type = type;
  oscillator.frequency.value = frequency;
  oscillator.connect(gain);
  gain.connect(ctx.destination);
  gain.gain.setValueAtTime(volume, start);
  gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
  oscillator.start(start);
  oscillator.stop(start + duration);
}

export function playMoveSound() {
  beep({ frequency: 440, duration: 0.09 });
}

export function playCaptureSound() {
  beep({ frequency: 200, duration: 0.16, type: "square" });
}

export function playCheckSound() {
  beep({ frequency: 700, duration: 0.1 });
  beep({ frequency: 700, duration: 0.1, delay: 0.14 });
}

export function playGameOverSound() {
  beep({ frequency: 330, duration: 0.2 });
  beep({ frequency: 220, duration: 0.35, delay: 0.2 });
}

/** Pick the right sound for a move response from the Xiangqi API. */
export function playSoundForMove({ captured, status }) {
  if (status === "checkmate" || status === "stalemate") {
    playGameOverSound();
  } else if (status === "check") {
    playCheckSound();
  } else if (captured) {
    playCaptureSound();
  } else {
    playMoveSound();
  }
}
