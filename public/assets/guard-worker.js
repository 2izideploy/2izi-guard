'use strict';
importScripts('guard-pow.js');

self.onmessage = async (event) => {
  const { challengeId, salt, difficulty } = event.data || {};
  try {
    const result = await self.IZIGuardPow.solve(challengeId, salt, Number(difficulty));
    self.postMessage({ ok: true, nonce: result.nonce, elapsedMs: result.elapsedMs });
  } catch (error) {
    self.postMessage({ ok: false, error: error?.message || 'pow_failed' });
  }
};
