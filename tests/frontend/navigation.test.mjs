import test from 'node:test';
import assert from 'node:assert/strict';
import {resolveHref} from '../../resources/js/navigation.js';

test('migrated named links retain encoded parameters, query and hash',()=>{
  assert.equal(resolveHref({name:'courseDetail',params:{id:42}}),'/kelas/42');
  assert.equal(resolveHref({name:'content-update',params:{courseId:3,contentId:9}}),'/course/update-contents/3/9');
  assert.equal(resolveHref({name:'information-document-create',query:{type:2,search:'gadai emas'},hash:'#form'}),'/information-document/create?type=2&search=gadai+emas#form');
  assert.equal(resolveHref('detail-pengumuman/7'),'/detail-pengumuman/7');
  assert.equal(resolveHref({name:'detailPage',params:{id:'a/b'}}),'/detail-course/a%2Fb');
});
test('incomplete navigation fails explicitly instead of going to a wrong record',()=>{
  assert.throws(()=>resolveHref({name:'content-preview',params:{}}),/Missing route parameter/);
  assert.throws(()=>resolveHref({name:'nonexistent'}),/Unknown page/);
});
