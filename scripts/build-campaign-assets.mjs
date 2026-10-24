import fs from 'node:fs/promises';
import sharp from 'sharp';

const names = ['alt-campaign', 'street-campaign', 'alice-portrait', 'mark-portrait', 'sofia-portrait', 'lera-portrait', 'anna-portrait', 'daniel-portrait'];
await fs.mkdir('public/images/campaign', { recursive: true });
await Promise.all(names.map(async name => {
    await sharp(`public/images/${name}.png`).resize({ width: 900, withoutEnlargement: true }).webp({ quality: 83 }).toFile(`public/images/campaign/${name}.webp`);
}));
await sharp('public/images/dog-witness.png').resize({ width: 800, withoutEnlargement: true }).webp({ quality: 90, alphaQuality: 100 }).toFile('public/images/campaign/dog-witness.webp');
const files = await fs.readdir('public/images/campaign');
const sizes = await Promise.all(files.map(async file => (await fs.stat(`public/images/campaign/${file}`)).size));
console.log(`Campaign assets: ${files.length} files, ${Math.round(sizes.reduce((sum, size) => sum + size, 0) / 1024)} KB total`);
