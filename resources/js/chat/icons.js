// Small inline icon set (stroke icons, 24x24). Static trusted markup only.
const p = (d, extra = '') =>
  `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" ${extra}>${d}</svg>`;

const icons = {
  lock: p('<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>'),
  send: p('<path d="M5 12 19.5 5l-4 14-3.2-5.3z"/><path d="m12.3 13.7 7.2-8.7"/>'),
  smile: p('<circle cx="12" cy="12" r="9"/><path d="M8.5 14.2c.8 1.2 2 1.8 3.5 1.8s2.7-.6 3.5-1.8"/><path d="M9 9.6v.2M15 9.6v.2"/>'),
  viewonce: p('<circle cx="12" cy="12" r="9" stroke-dasharray="2.2 2.6"/><path d="M10.4 9.6 12.4 8.5v7"/>'),
  reply: p('<path d="M9.5 6 4 11.5 9.5 17"/><path d="M4 11.5h9a6.5 6.5 0 0 1 6.5 6.5"/>'),
  trash: p('<path d="M4.5 7h15M10 11v6M14 11v6M6.5 7l.8 11.2A2 2 0 0 0 9.3 20h5.4a2 2 0 0 0 2-1.8L17.5 7M9.5 7V4.8c0-.4.3-.8.8-.8h3.4c.5 0 .8.4.8.8V7"/>'),
  copy: p('<rect x="8.5" y="8.5" width="11" height="11" rx="2.5"/><path d="M15.5 8.5V6.5a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h2"/>'),
  x: p('<path d="m6 6 12 12M18 6 6 18"/>'),
  sliders: p('<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>'),
  logout: p('<path d="M14 4.5h3.5a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H14"/><path d="M10 8.5 5.5 12 10 15.5M5.5 12H15"/>'),
  shield: p('<path d="M12 3.5 5 6v5.5c0 4.2 2.8 7.6 7 9 4.2-1.4 7-4.8 7-9V6z"/><path d="m9 12 2.2 2.2L15.2 10"/>'),
  ghost: p('<path d="M5 20.5V11a7 7 0 0 1 14 0v9.5l-2.4-1.9-2.3 1.9-2.3-1.9-2.3 1.9-2.3-1.9z"/><path d="M9.3 11v1.2M14.7 11v1.2"/>'),
  clock: p('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
  eyeoff: p('<path d="M3 3l18 18"/><path d="M10.6 5.2A9.7 9.7 0 0 1 12 5c5 0 8.5 4.5 9.5 7-.4 1-1.2 2.4-2.4 3.7M6.7 6.8C4.7 8.2 3.3 10.3 2.5 12c1 2.5 4.5 7 9.5 7 1.7 0 3.2-.5 4.5-1.2"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>'),
  eye: p('<path d="M2.5 12C3.5 9.5 7 5 12 5s8.5 4.5 9.5 7c-1 2.5-4.5 7-9.5 7S3.5 14.5 2.5 12z"/><circle cx="12" cy="12" r="3"/>'),
  arrowdown: p('<path d="M12 5v14M6 13l6 6 6-6"/>'),
  back: p('<path d="m14.5 6-6 6 6 6"/>'),
  check: p('<path d="m5 12.5 4.2 4.2L19 7"/>', 'width="14" height="14"'),
  checks: p('<path d="m2.5 12.8 4 4L15 8"/><path d="m10.5 15.6 1.2 1.2L21 7.5"/>', 'width="16" height="16"'),
  more: p('<path d="m7 10 5 5 5-5"/>', 'width="16" height="16"'),
  flame: p('<path d="M12 3.5c.5 3-2.5 4.5-2.5 7.5 0 1.6 1 2.5 2 2.5.8 0 1.5-.6 1.5-1.6 0-.5-.2-.9-.4-1.3 2 .8 3.9 3 3.9 5.4A5 5 0 0 1 12 20.5a5 5 0 0 1-5-5c0-4.5 4-6 5-12z"/>'),
  info: p('<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.2"/>'),
  warn: p('<path d="M12 4 3 19.5h18z"/><path d="M12 10v4.5M12 17.2v.2"/>'),
  mail: p('<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m4.5 7.5 7.5 6 7.5-6"/>'),
  key: p('<circle cx="8" cy="15" r="3.5"/><path d="m10.5 12.5 8-8M15.5 7.5l2.5 2.5M13.5 9.5l1.5 1.5"/>'),
  sound: p('<path d="M5 9.5v5h3.5l4.5 3.5v-12L8.5 9.5z"/><path d="M16.5 9a4 4 0 0 1 0 6"/>'),
  plus: p('<path d="M12 5v14M5 12h14"/>'),
};

export const icon = (name) => icons[name] || '';
