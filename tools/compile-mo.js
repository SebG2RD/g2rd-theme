#!/usr/bin/env node
'use strict';
/**
 * Compilateur .po → .mo minimal (format GNU MO v0, little-endian).
 * Utilisé en dev Windows où msgfmt n'est pas disponible.
 * En CI (Ubuntu), msgfmt prend le relais.
 */
const fs   = require('fs');
const path = require('path');

const SRC = path.resolve(__dirname, '..', 'languages', 'fr_FR.po');
const DST = path.resolve(__dirname, '..', 'languages', 'fr_FR.mo');

const po   = fs.readFileSync(SRC, 'utf8');
const lines = po.split('\n');

const entries = [];
let msgid = null, msgstr = null, inId = false, inStr = false;

for (const raw of lines) {
	const line = raw.trimEnd();
	if (line.startsWith('msgid "')) {
		if (msgid !== null && msgstr !== null) entries.push({ id: msgid, str: msgstr });
		msgid  = line.slice(7, -1);
		msgstr = null;
		inId = true; inStr = false;
	} else if (line.startsWith('msgstr "')) {
		msgstr = line.slice(8, -1);
		inId = false; inStr = true;
	} else if (line.startsWith('"')) {
		const chunk = line.slice(1, -1);
		if (inId)  msgid  += chunk;
		if (inStr) msgstr += chunk;
	} else {
		inId = false; inStr = false;
	}
}
if (msgid !== null && msgstr !== null) entries.push({ id: msgid, str: msgstr });

const valid = entries.filter(e => e.id !== '' && e.str !== '');
valid.sort((a, b) => (a.id < b.id ? -1 : 1));

function unescape(s) {
	return s
		.replace(/\\n/g, '\n')
		.replace(/\\t/g, '\t')
		.replace(/\\\\/g, '\\')
		.replace(/\\"/g, '"');
}

function toEntry(s) {
	const data = Buffer.from(unescape(s), 'utf8');
	const nul  = Buffer.alloc(1, 0);
	return Buffer.concat([data, nul]);
}

const n = valid.length;
const origBufs  = valid.map(e => toEntry(e.id));
const transBufs = valid.map(e => toEntry(e.str));

// Offsets: header(28) + origTable(n*8) + transTable(n*8) + strings
const strStart = 28 + n * 8 + n * 8;
let origOff = strStart;
const origOffsets = origBufs.map(b => { const o = origOff; origOff += b.length; return o; });
let transOff = origOff;
const transOffsets = transBufs.map(b => { const o = transOff; transOff += b.length; return o; });

const header = Buffer.alloc(28);
header.writeUInt32LE(0x950412de, 0);   // magic LE
header.writeUInt32LE(0,          4);   // revision
header.writeUInt32LE(n,          8);   // num strings
header.writeUInt32LE(28,         12);  // orig table offset
header.writeUInt32LE(28 + n * 8, 16); // trans table offset
header.writeUInt32LE(0,          20);
header.writeUInt32LE(0,          24);

const origTable  = Buffer.alloc(n * 8);
const transTable = Buffer.alloc(n * 8);
for (let i = 0; i < n; i++) {
	origTable.writeUInt32LE(origBufs[i].length - 1, i * 8);
	origTable.writeUInt32LE(origOffsets[i],          i * 8 + 4);
	transTable.writeUInt32LE(transBufs[i].length - 1, i * 8);
	transTable.writeUInt32LE(transOffsets[i],          i * 8 + 4);
}

const out = Buffer.concat([header, origTable, transTable, ...origBufs, ...transBufs]);
fs.writeFileSync(DST, out);
console.log(`OK: fr_FR.mo — ${n} traductions, ${out.length} octets`);
