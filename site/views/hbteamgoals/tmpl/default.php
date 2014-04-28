<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>

<div class="hbteam">
<h1><?php echo $this->team->mannschaft; ?>
<span><?php echo $this->team->liga; ?></span></h1>

	<h3>Torschützen</h3>	


	
<div id="scorerTable" data-teamkey="<?php echo $this->teamkey;?>" data-season="<?php echo $this->season;?>">	
	<table class="goals">
		<thead><tr>
				<th class="name">Name</th>
				<th><?php JHTML::_('behavior.tooltip');
				echo JHTML::tooltip('Tore im ausgewählten Spiel', 'Tore', '', 'T');?>
				</th>
				<th><?php JHTML::_('behavior.tooltip');
				echo JHTML::tooltip('Anzahl der gespielten Spiele (bis zum ausgewählten Spiel)', 'Spiele', '', 'S');?>
				</th>
				<th><?php JHTML::_('behavior.tooltip');
				echo JHTML::tooltip('Anzahl der insgesamt erzielten Tore (bis zum ausgewählten Spiel)', 'Gesamt-Tore', '', 'G');?>
				</th>
				<th><?php JHTML::_('behavior.tooltip');
				echo JHTML::tooltip('Durchschnittliche Anzahl der Tore pro Spiel (bis zum ausgewählten Spiel)', 'Tore/Spiel', '', 'T/S');?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php

foreach ($this->players as $player) 
{
	//echo '=> view->players<br><pre>'; print_r($player); echo '</pre>';
	echo '<tr';
	if ($player->tore === null)echo ' class="notPlayed"';
	echo '><td class="name">';
	echo $player->name;
	if ($player->tw == true or $player->twposition) {
		echo ' (TW)';
	}
	echo '</td><td class="goals">';
	echo $player->tore;
	if ($player->davon7m != null) {
		echo '/'.$player->davon7m;
	}
	echo '</td><td>';
	echo $player->spiele;
	echo '</td><td>';
	echo $player->toregesamt;
	echo '</td><td>';
	echo $player->quote;
	echo '</td></tr>';
}
?>
	</tbody>
	</table>
</div>


<div class="moreGames">
	<div data-teamkey="<?php echo $this->teamkey;?>" style="display: hidden"></div>
	<div class="dataSaison" id="<?php echo $this->season;?>" style="display: hidden"></div>
	
	<table id="moreGames">
<?php
foreach ($this->games as $game) 
{
	?>
	<tr class="gamebutton<?php
	if ($game->spielIDhvw === $this->gameId) echo ' selected';
		?>" id="<?php echo $game->spielIDhvw;?>">
		<td class="date"><?php 
		echo JHtml::_('date', $game->datum, 'd. M.', false);
		//echo JHtml::_('date', $game->datum, 'd.m.y', false);
		?></td>
		<td class="moreGamesTeams"><?php 
		echo $game->gameName;
		?></td>
		<td><span class="moreGamesResult"><?php 
		echo $game->toreHeim;
		echo ':';
		echo $game->toreGast;
		?></span></td>
	</tr>
	<?php
}
?>
	</table>
</div>
	<div class="clr"></div>
	
	<div id="chartgoals">

	</div>
	
	<div id="chartgoalstotal">

	</div>
</div>