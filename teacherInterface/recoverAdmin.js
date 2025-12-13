// Recovery Interface JavaScript

let selectedTeams = [];
let scoresData = [];

// DynamoDB Scan
function scanDynamoDB() {
   showLoading('dynamodb');
   hideResults('dynamodb');
   
   $.post('recoverApi.php', { action: 'scanDynamoDB' }, function(response) {
      hideLoading('dynamodb');
      if(response.success) {
         displayDynamoDBResults(response.stats);
      } else {
         showError('dynamodb', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('dynamodb');
      showError('dynamodb', 'Request failed');
   });
}

function displayDynamoDBResults(stats) {
   const statsHtml = `
      <table>
         <tr><td>Items Scanned</td><td>${stats.scanned || 0}</td></tr>
         <tr><td>New Answers</td><td>${stats.new || 0}</td></tr>
         <tr><td>QR Codes</td><td>${stats.qrcode || 0}</td></tr>
         <tr><td>Classic Format</td><td>${stats.classic || 0}</td></tr>
         <tr><td>Errors</td><td>${stats.error || 0}</td></tr>
         <tr><td>Not Found</td><td>${stats.notfound || 0}</td></tr>
      </table>
   `;
   
   $('#dynamodb-stats').html(statsHtml);
   $('#dynamodb-details').html('<p>Scan completed successfully. Data saved to team_question_other table.</p>');
   showResults('dynamodb');
}

// Error Log - Answers
function scanErrorLogAnswers() {
   const startDate = $('#errorlog-start-date').val();
   const endDate = $('#errorlog-end-date').val();
   
   showLoading('errorlog-answers');
   hideResults('errorlog-answers');
   
   $.post('recoverApi.php', { 
      action: 'scanErrorLogAnswers',
      startDate: startDate,
      endDate: endDate
   }, function(response) {
      hideLoading('errorlog-answers');
      if(response.success) {
         displayErrorLogAnswersResults(response.stats);
      } else {
         showError('errorlog-answers', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('errorlog-answers');
      showError('errorlog-answers', 'Request failed');
   });
}

function displayErrorLogAnswersResults(stats) {
   const statsHtml = `
      <table>
         <tr><td>Processed</td><td>${stats.processed || 0}</td></tr>
         <tr><td>Recovered</td><td>${stats.recovered || 0}</td></tr>
         <tr><td>Invalid</td><td>${stats.invalid || 0}</td></tr>
         <tr><td>Team Not Found</td><td>${stats.teamNotFound || 0}</td></tr>
      </table>
   `;
   
   $('#errorlog-answers-stats').html(statsHtml);
   showResults('errorlog-answers');
}

// Error Log - Scores
function scanErrorLogScores() {
   const startDate = $('#errorlog-start-date').val();
   const endDate = $('#errorlog-end-date').val();
   
   showLoading('errorlog-scores');
   hideResults('errorlog-scores');
   
   $.post('recoverApi.php', { 
      action: 'scanErrorLogScores',
      startDate: startDate,
      endDate: endDate
   }, function(response) {
      hideLoading('errorlog-scores');
      if(response.success) {
         displayErrorLogScoresResults(response.stats);
      } else {
         showError('errorlog-scores', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('errorlog-scores');
      showError('errorlog-scores', 'Request failed');
   });
}

function displayErrorLogScoresResults(stats) {
   const statsHtml = `
      <table>
         <tr><td>Processed</td><td>${stats.processed || 0}</td></tr>
         <tr><td>Recovered</td><td>${stats.recovered || 0}</td></tr>
         <tr><td>Invalid</td><td>${stats.invalid || 0}</td></tr>
      </table>
   `;
   
   $('#errorlog-scores-stats').html(statsHtml);
   showResults('errorlog-scores');
}

// Preview Merge
function previewMerge() {
   const contestID = $('#merge-contest').val().trim();
   
   showLoading('merge');
   hideResults('merge');
   
   $.post('recoverApi.php', { 
      action: 'previewMerge',
      contestID: contestID
   }, function(response) {
      hideLoading('merge');
      if(response.success) {
         displayMergeResults(response.stats, true);
      } else {
         showError('merge', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('merge');
      showError('merge', 'Request failed');
   });
}

// Execute Merge
function executeMerge() {
   const contestID = $('#merge-contest').val().trim();
   
   showLoading('merge');
   hideResults('merge');
   
   $.post('recoverApi.php', { 
      action: 'executeMerge',
      contestID: contestID
   }, function(response) {
      hideLoading('merge');
      if(response.success) {
         displayMergeResults(response.stats, false);
      } else {
         showError('merge', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('merge');
      showError('merge', 'Request failed');
   });
}

function displayMergeResults(stats, isPreview) {
   const statsHtml = `
      <table>
         <tr><td>Analyzed</td><td>${stats.analyzed || 0}</td></tr>
         <tr><td>Improved</td><td>${stats.improved || 0}</td></tr>
         <tr><td>Unchanged</td><td>${stats.unchanged || 0}</td></tr>
         ${!isPreview ? `<tr><td>Archived</td><td>${stats.archived || 0}</td></tr>` : ''}
         ${!isPreview ? `<tr><td>Updated</td><td>${stats.updated || 0}</td></tr>` : ''}
      </table>
   `;
   
   $('#merge-stats').html(statsHtml);
   showResults('merge');
}

// Compare Scores
function compareScores() {
   const contestID = $('#scores-contest').val().trim();
   
   showLoading('scores');
   hideResults('scores');
   
   $.post('recoverApi.php', { 
      action: 'compareScores',
      contestID: contestID || null
   }, function(response) {
      hideLoading('scores');
      if(response.success) {
         scoresData = response.results;
         displayScoresResults(response.results);
      } else {
         showError('scores', response.error);
      }
   }, 'json').fail(function() {
      hideLoading('scores');
      showError('scores', 'Request failed');
   });
}

function displayScoresResults(results) {
   if(results.length === 0) {
      $('#scores-list').html('<p>No teams found with better scores.</p>');
   } else {
      let html = '<table><tr><th>Select</th><th>Team ID</th><th>Current Score</th><th>Recovered Score</th><th>Improvement</th></tr>';
      results.forEach(function(team) {
         const improvement = team.recoveredScore - team.currentScore;
         html += `
            <tr>
               <td><input type="checkbox" class="score-checkbox" value="${team.teamID}" checked></td>
               <td>${team.teamID}</td>
               <td>${team.currentScore}</td>
               <td>${team.recoveredScore}</td>
               <td>+${improvement}</td>
            </tr>
         `;
      });
      html += '</table>';
      $('#scores-list').html(html);
   }
   showResults('scores');
}

function selectAllScores() {
   $('.score-checkbox').prop('checked', true);
}

function deselectAllScores() {
   $('.score-checkbox').prop('checked', false);
}

// Apply Scores
function applyScores() {
   const checkedBoxes = $('.score-checkbox:checked');
   if(checkedBoxes.length === 0) {
      return;
   }
   
   const teamIDs = [];
   checkedBoxes.each(function() {
      teamIDs.push($(this).val());
   });
   
   showLoading('scores');
   $('#scores-apply-results').hide();
   
   $.post('recoverApi.php', { 
      action: 'applyScores',
      'teamIDs[]': teamIDs
   }, function(response) {
      hideLoading('scores');
      if(response.success) {
         $('#scores-apply-results').html(`
            <p>Scores applied successfully!</p>
            <table>
               <tr><td>Applied</td><td>${response.stats.applied}</td></tr>
               <tr><td>Archived</td><td>${response.stats.archived}</td></tr>
            </table>
         `).show();
         
         // Refresh comparison
         compareScores();
      } else {
         $('#scores-apply-results').html(`
            <p class="error">Error: ${response.error}</p>
         `).show();
      }
   }, 'json').fail(function() {
      hideLoading('scores');
      $('#scores-apply-results').html(`
         <p class="error">Request failed</p>
      `).show();
   });
}

// Helper functions
function showLoading(section) {
   $('#' + section + '-loading').addClass('active');
}

function hideLoading(section) {
   $('#' + section + '-loading').removeClass('active');
}

function showResults(section) {
   $('#' + section + '-results').show();
}

function hideResults(section) {
   $('#' + section + '-results').hide();
}

function showError(section, message) {
   $('#' + section + '-results').html(`
      <p class="error">Error: ${message}</p>
   `).show();
}

// Initialize
$(document).ready(function() {
   // Ready
});
