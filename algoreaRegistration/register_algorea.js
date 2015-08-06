'use strict';

function validateCodeForm() {
   var code = $('#code').val();
   if (!code) { return; }
   console.log(code);
   $.post('register_algorea.php', {action: 'getInfos', code:code}, function(data) {
      console.log(data);
      console.log('toto');
      if (!data || !data.success) {
         $('#errorMessage').html(data.message);
         return;
      }
      var infos = data.infos;
      if (infos.emailValidated) {
         $('#errorMessage').html('Ce code a déjà été validé, merci de contacter votre professeur si vous pensez qu\'il s\'agit d\'une erreur.');
         return;
      }
      $('#firstName').val(infos.firstName);
      $('#lastName').val(infos.lastName);
      $('#email').val(infos.email);
      $('#emailVerif').val(infos.email);
      $('#algoreaAccount').val(infos.algoreaAccount);
      $('#codeFormDiv').hide();
      $('#infosFormDiv').show();
      $('#errorMessage').html('');
   }, 'json');
   return false;
}

function validateInfosForm() {
   var infos = {
      'firstName':      $('#firstName').val(),
      'lastName':       $('#lastName').val(),
      'email':          $('#email').val(),
      'emailVerif':     $('#emailVerif').val(),
      'algoreaAccount': $('#algoreaAccount').val(),
      'code':           $('#code').val()
   };
   console.log(infos);
   if (!infos.firstName || !infos.lastName) {
      $('#errorMessage').html('prénom  et nom obligatoires');
      return false;
   }
   if (!infos.email || !infos.emailVerif || infos.email != infos.emailVerif) {
      $('#errorMessage').html('mail obligatoire, et les emails doivent être identiques');
      return false;
   }
   $.post('register_algorea.php', {action: 'registerInfos', infos:infos}, function(data) {
      if (!data || !data.success) {
         $('#errorMessage').html(data.message);
         return;
      }
      $('#errorMessage').html('');
      $('#codeFormDiv').show();
      $('#code').val('');
      $('#infosFormDiv').hide();
      alert("Merci pour votre inscription ! Vous allez recevoir un email de confirmation. Pensez-bien à le consulter pour finaliser votre inscription !");
   }, 'json');
}

$.urlParam = function(name){
   var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
   if (!results) { return 0; }
   return results[1] || 0;
}

$(window).load(function() {
    var hash = $.urlParam('hash');
   if (hash) {
      $('#codeFormDiv').hide();
      $('#loadingDiv').show();
      $.post('register_algorea.php', {action: 'verifyEmail', hash:hash}, function(data) {
         $('#loadingDiv').hide();
         if (!data || !data.success) {
            alert(data.message);
            location.search = location.search.replace(/\?hash=[^&;]*/,'');
            return;
         }
         $('#codeFormDiv').show();
         $('#errorMessage').html('');
         alert("Merci pour la finalisation de votre inscription ! Nous vous recontacterons un peu avant le concours.");
         location.search = location.search.replace(/\?hash=[^&;]*/,'');
      }, 'json');
   }  
});

