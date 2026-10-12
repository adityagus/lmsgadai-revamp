import test from 'node:test';
import assert from 'node:assert/strict';
import { errorMessage } from '../../resources/js/pages/quiz/errors.mjs';

test('quiz failures distinguish slow server, expired session, permissions, and network', () => {
    assert.match(errorMessage({ code: 'ECONNABORTED' }), /batas waktu/);
    assert.match(errorMessage({ response: { status: 401 } }), /Sesi login/);
    assert.match(errorMessage({ response: { status: 403 } }), /akses/);
    assert.match(errorMessage({ code: 'ERR_NETWORK' }), /tidak dapat dijangkau/);
    assert.match(errorMessage({ response: { status: 500, data: { message: 'SQL secret' } } }), /Server sedang/);
    assert.equal(errorMessage({ response: { status: 422, data: { errors: { title: ['Judul wajib diisi.'] } } } }), 'Judul wajib diisi.');
    assert.equal(errorMessage({}), 'Data belum berhasil dimuat. Silakan coba lagi.');
});
