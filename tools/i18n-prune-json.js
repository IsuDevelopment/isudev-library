/**
 * Delete JSON translations made for src/ files. make-json writes one file per
 * referenced JS source; WordPress only looks up the md5 of the build/ script
 * path, so src/ ones are dead weight. See "Translations" in AGENTS.md.
 */
/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');

const dir = path.resolve(__dirname, '../languages');

for (const file of fs.readdirSync(dir)) {
	if (!file.endsWith('.json')) {
		continue;
	}
	const full = path.join(dir, file);
	const { source = '' } = JSON.parse(fs.readFileSync(full, 'utf8'));
	if (!source.startsWith('build/')) {
		fs.unlinkSync(full);
	} else {
		console.log(`${file} -> ${source}`);
	}
}
