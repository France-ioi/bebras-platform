<?php
  
require_once('./config.php');
require_once("../shared/common.php");
?>
<html>
<head>
<meta charset='utf-8'>
<?php
script_tag('/bower_components/jquery/jquery.min.js');
script_tag('/monitor.js');
?>
<style>
.monitor-table, .monitor-table td {
    border-collapse: collapse;
    border: 1px solid black;
    padding: 8px;
}
thead {
    font-weight: bold;
    background-color: #ddd;
}
.team-code {
    font-family: monospace, arial, serif;
}
.team-finished {
    background-color: lightblue;
}
.team-ok {
    background-color: lightgreen;
}
.team-warning {
    background-color: yellow;
}
.team-error {
    background-color: salmon;
}
#check-error {
    color: red;
}
</style>
</head>
<body>
<h1>Surveillance de l'état de connexion d'un groupe</h1>
<p>Cette page vous permet de surveiller l'état de connexion des participants d'un groupe, et vous affiche si une équipe semble avoir perdu sa connexion.</p>
<p>
    Veuillez entrer un code de secours du groupe à surveiller :
    <input type="text" id="groupPassword" value="">
    <input type="button" value="Valider" onclick="checkGroup();">
    <span id="check-error"></span>
</p>
<hr>
<p><b>Options d'affichage :</b></p>
<p>Trier par :
    <select id="sortBy" onchange="renderTable();">
        <option value="status" selected>État de connexion</option>
        <option value="name">Nom</option>
        <option value="createTime">Date de création</option>
    </select>
</p>
<p><input type="checkbox" id="showTeamCodes" onchange="renderTable();"> Afficher les codes d'équipe complets</p>
<p>Dernière mise à jour : <span id="update-time">-</span> <i>(mise à jour toutes les minutes)</i></p>
<table class="monitor-table">
    <thead>
        <tr>
            <td rowspan="2">Informations</td>
            <td rowspan="2">État de connexion</td>
            <td colspan="2">Dernière activité</td>
            <td colspan="4">Déroulé de la participation</td>
        </tr>
        <tr>
            <td>Dernier ping</td>
            <td>Dernière réponse</td>
            <td>Création</td>
            <td>Début de l'épreuve</td>
            <td>Fin de l'épreuve</td>
            <td>Soumission des dernières réponses</td>
        </tr>
    </thead>
    <tbody id="result-body">
    </tbody>    
</table>