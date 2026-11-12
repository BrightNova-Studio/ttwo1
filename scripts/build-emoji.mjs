// Builds a compact, self-hosted emoji list so the browser never calls a third party.
import fs from 'node:fs';
const src = JSON.parse(fs.readFileSync('node_modules/emoji-picker-element-data/en/emojibase/data.json', 'utf8'));
const out = src
  .filter(d => d.group !== undefined && d.group !== 2 && d.version <= 15)
  .sort((a, b) => a.order - b.order)
  .map(d => {
    const o = { e: d.emoji, g: d.group, n: d.annotation, k: [...(d.shortcodes || []), ...(d.tags || [])].join(' ') };
    if (d.skins) o.s = d.skins.map(s => s.emoji);
    return o;
  });
fs.writeFileSync('public/emoji/emoji.json', JSON.stringify(out));
console.log(out.length, 'emojis,', (fs.statSync('public/emoji/emoji.json').size / 1024).toFixed(0), 'KB');
