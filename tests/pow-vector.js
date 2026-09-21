'use strict';
global.self = global;
global.performance = require('perf_hooks').performance;
require('../public/assets/guard-pow.js');
const assert = (cond, msg) => { if (!cond) { console.error(`FAIL: ${msg}`); process.exit(1); } console.log(`OK: ${msg}`); };
assert(self.IZIGuardPow.hex(self.IZIGuardPow.sha256Ascii('abc')) === 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad', 'SHA-256 known vector');
(async () => {
  const r = await self.IZIGuardPow.solve('ch_test', 'salt_test', 12);
  const words = self.IZIGuardPow.sha256Ascii(`v1|ch_test|salt_test|${r.nonce}`);
  assert(self.IZIGuardPow.leadingZeroBits(words) >= 12, 'PoW solver matches protocol');
  console.log(`PoW vector passed. nonce=${r.nonce}, ms=${r.elapsedMs}`);
})().catch((e) => { console.error(e); process.exit(1); });
