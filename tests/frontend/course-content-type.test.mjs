import test from 'node:test';
import assert from 'node:assert/strict';
import { courseContentType, googleFormUrl } from '../../resources/js/utils/courseContentType.mjs';

test('course 51 unlinked quiz stays a native quiz, not Google Form', () => {
    const content = { id: 89, course_id: 51, type: 'quiz', quiz_id: null, content: 'asdasd' };
    assert.equal(courseContentType(content), 'quiz');
    assert.equal(googleFormUrl(content), '');
});

test('empty quiz stays a quiz and linked quiz always uses native renderer', () => {
    assert.equal(courseContentType({type:'quiz',content:null}), 'quiz');
    assert.equal(courseContentType({type:'quiz',quiz_id:12,content:'https://docs.google.com/forms/d/old/viewform'}), 'quiz');
});

test('legacy real Google Forms remain compatible', () => {
    for (const content of ['https://docs.google.com/forms/d/e/example/viewform', 'https://forms.gle/example', '<iframe src="https://docs.google.com/forms/d/e/example/viewform?embedded=true"></iframe>']) {
        assert.equal(courseContentType({type:'quiz',content}), 'google_form');
        assert.ok(googleFormUrl({content}).startsWith('https://'));
    }
});

test('explicit Google Form with an invalid URL shows an empty state', () => {
    assert.equal(courseContentType({type:'google_form',content:'invalid'}), 'google_form');
    assert.equal(googleFormUrl('invalid'), '');
});

test('non-Google URLs and misleading hosts are not Google Forms', () => {
    for (const content of ['https://docs.google.com.evil.test/forms/a', 'javascript:alert(1)', 'https://example.test/google.com/forms/a', 'https://docs.google.com/document/d/a', 'http://docs.google.com/forms/a']) {
        assert.equal(googleFormUrl(content), '');
        assert.equal(courseContentType({type:'quiz',content}), 'quiz');
    }
});

test('other course materials retain their original types', () => {
    for (const type of ['text','video','pdf']) assert.equal(courseContentType({type,content:'some content'}), type);
});
