const scoreState = {matchId: null};

async function loadScoringMatch(id) {
  if (!id) return;
  scoreState.matchId = id;
  try {
    const {data:m} = await api(`${APP_BASE}/api/get_match.php?id=${id}`);
    $('#team1-name').textContent = m.participant1_name || 'TBD'; $('#team2-name').textContent = m.participant2_name || 'TBD';
    $('#score1').textContent = String(m.score1).padStart(2,'0'); $('#score2').textContent = String(m.score2).padStart(2,'0');
    $('#score-status').textContent = m.status.toUpperCase(); $('#last-updated').textContent = new Date().toLocaleTimeString('id-ID');
    $$('.score-action').forEach(b => b.disabled = m.status === 'finished');
  } catch(e) { toast(e.message,'error'); }
}

async function changeScore(slot, delta) {
  try {
    await api(`${APP_BASE}/api/update_score.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({match_id:scoreState.matchId,slot,delta,csrf_token:csrf()})});
    toast('Score updated successfully'); loadScoringMatch(scoreState.matchId);
  } catch(e) { toast(e.message,'error'); }
}

async function setScore(slot) {
  const input = document.querySelector(`#set-score-${slot}`); const score = Number(input?.value);
  if (!Number.isInteger(score) || score < 0) return toast('Enter a valid score', 'error');
  try {
    await api(`${APP_BASE}/api/update_score.php`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({match_id:scoreState.matchId,slot,score,csrf_token:csrf()})});
    toast('Score set successfully'); input.value=''; loadScoringMatch(scoreState.matchId);
  } catch(e) { toast(e.message,'error'); }
}

async function matchAction(action) {
  if ((action==='finish' || action==='reset') && !confirm(`${action === 'finish' ? 'Finish' : 'Reset'} this match?`)) return;
  try {
    await api(`${APP_BASE}/api/${action}_match.php`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({match_id:scoreState.matchId,csrf_token:csrf()})});
    toast(`Match ${action} successful`); loadScoringMatch(scoreState.matchId);
  } catch(e) { toast(e.message,'error'); }
}

$('#match-select')?.addEventListener('change', e => loadScoringMatch(e.target.value));
$$('[data-score]').forEach(b => b.addEventListener('click', () => changeScore(+b.dataset.slot,+b.dataset.score)));
$$('[data-set-score]').forEach(b => b.addEventListener('click', () => setScore(+b.dataset.setScore)));
$$('[data-match-action]').forEach(b => b.addEventListener('click', () => matchAction(b.dataset.matchAction)));
if ($('#match-select')?.value) loadScoringMatch($('#match-select').value);
