<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
 
/**
 * HB Team Home Model
 */
class hbteamModelHBteamGoals extends JModelLegacy
{
	/**
	 * @var array messages
	 */
	public $teamkey;
	public $season;
	public $gameId;
	
	function __construct() 
	{
		parent::__construct();
		
		//request the selected teamkey
		$jinput = JFactory::getApplication()->input;
		//echo '=> model->gameId<br><pre>'; print_r($jinput); echo '</pre>';
		$menuitemid = $jinput->get('Itemid');	
			//echo '=> model->gameId<br><pre>'; print_r($menuitemid); echo '</pre>';
			if ($menuitemid)
			{
				$menu = JFactory::getApplication()->getMenu();
				$menuparams = $menu->getParams($menuitemid);
				$this->teamkey = $menuparams->get('teamkey');
				$this->season = $menuparams->get('season');
			}
			else {
				$this->teamkey = $jinput->get('teamkey');
				$this->season = $jinput->get('season');
			}
			$game = self::getRecentGame();
			if (!empty($game)) {
				$this->gameId = $game->spielIdHvw;
				$this->gameDate = $game->datum;
			}
			
	}
	
	function getTeam($teamkey = "non")
	{
		if ($teamkey === "non"){
			$teamkey = $this->teamkey;
		}
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('hb_mannschaft');
		$query->where($db->qn('kuerzel').' = '.$db->q($teamkey));
		//echo '=> model->$query <br><pre>"; print_r($query); echo "</pre>';
		$db->setQuery($query);
		$team = $db->loadObject();
		return $team;
	}
	
	protected function getRecentGame($teamkey = null)
	{
		if ($teamkey === null){
			$teamkey = $this->teamkey;
		}
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('spielIdHvw, DATE(`datumZeit`) AS `datum`');
		$query->from('hb_spiel_spieler');
		$query->leftJoin($db->qn('hb_spiel').' USING ('.$db->qn('spielIDhvw').')');
		$query->group($db->qn('spielIdHvw'));
		$query->where('hb_spiel_spieler.'.$db->qn('kuerzel').' = '.$db->q($teamkey));
		$query->where($db->qn('eigenerVerein').' = 1');
		$query->where($db->qn('datumZeit').' < NOW() ');
		$query->order($db->qn('datumZeit').' DESC');
		//echo '=> model->$query <br><pre>'.$query.'</pre>';
		$db->setQuery($query);
		$game = $db->loadObject();
		//echo '=> model->gameId<br><pre>'; print_r($game); echo '</pre>';
		return $game;
	}
	
	function getGames($teamkey = "non")
	{
		if ($teamkey === "non"){
			$teamkey = $this->teamkey;
		}
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*, DATE(`datumZeit`) AS `datum`');
		$query->from('hb_spiel');
		$query->leftJoin($db->qn('hb_spiel_spieler').' USING ('.$db->qn('spielIDhvw').')');
		$query->where('hb_spiel.'.$db->qn('kuerzel').' = '.$db->q($teamkey));
		$query->where('hb_spiel.'.$db->qn('toreHeim').' IS NOT NULL');
		$query->where($db->qn('eigenerVerein').' = 1');
		$query->where($db->qn('datumZeit').' < NOW() ');
		$query->order($db->qn('datumZeit').' ASC');
		$query->group('spielIDhvw');
		//echo '=> model->$query <br><pre>"; print_r($query); echo "</pre>';
		$db->setQuery($query);
		$games = $db->loadObjectList();
		//echo '=> model->games<br><pre>'; print_r($games); echo '</pre>';
		
		foreach ($games as $game)
		{
			$gameName = $game->heim.'&'.$game->gast;
			$pattern = '/(.*)&(TSV Geislingen)/';
			$replacement = '${1} (A)';
			$gameName = preg_replace($pattern, $replacement, $gameName);
			$pattern = '/(TSV Geislingen)&(.*)/';
			$replacement = '${2} (H)';
			$gameName = preg_replace($pattern, $replacement, $gameName);
			$game->gameName = $gameName;
		}
		return $games;
	}
	
	function getPlayers($gameId = null, $teamkey = null, $season = null)
	{
		if ($teamkey === null){
			$teamkey = $this->teamkey;
		}
		if ($gameId === null){
			$gameId = $this->gameId;
		}
		if ($season === null){
			$season = $this->season;
		}
		//echo 'GameID: '.$gameId;
		
		$db = $this->getDbo();
		
		$query = $db->getQuery(true);
		$query->select('DATE(`datumZeit`) AS `datum`');
		$query->from('hb_spiel');
		$query->where($db->qn('spielIdHvw').' = '.$db->q($gameId));
//		//echo '=> model->$query <br><pre>'; echo $query; echo '</pre>';
//		$db->setQuery($query);
//		$this->gameDate = $db->loadResult();
//		//echo '=> model->gameDate<br><pre>'; print_r($this->gameDate); echo '</pre>';
		
		$db = $this->getDbo();
		//$season = $season.'/'.(season+1);
		$totalQuery = $db->getQuery(true);
		$totalQuery->select('alias, count(tore) AS spiele, sum(tore) AS toregesamt, '.
			'ROUND(sum(tore) / count(tore), 1) AS quote');
		$totalQuery->from('hb_spiel_spieler');
		$totalQuery->leftJoin($db->qn('hb_spiel').' USING ('.$db->qn('spielIDhvw').')');
		$totalQuery->where('hb_spiel_spieler.'.$db->qn('saison').' = '.$db->q($season.'/'.($season+1)));
		$totalQuery->where($db->qn('datumZeit').' <= '.$db->q($this->gameDate));
		$totalQuery->where($db->qn('trikotNr').' != 0');
		$totalQuery->group('alias');
		$totalQuery->order($db->qn('datumZeit').' ASC');

//		echo '=> model->$totalQuery <br><pre>'; echo $totalQuery; echo '</pre>';
//		$db->setQuery($query);
//		$players = $db->loadObjectList();
//		echo '=> model->players<br><pre>'; print_r($players); echo '</pre>';
		
		$innerQuery = $db->getQuery(true);
		$innerQuery->select('`alias`');
		$innerQuery->from('hb_spiel_spieler');
		$innerQuery->group($db->qn('alias'));
		$innerQuery->where($db->qn('kuerzel').' = '.$db->q($teamkey));
		$innerQuery->where($db->qn('trikotNr').' != 0');
		//echo '=> model->$query <br><pre>'; echo $query; echo '</pre>';
		$db->setQuery($innerQuery);
		$players = $db->loadObjectList();
		//echo '=> model->players<br><pre>'; print_r($players); echo '</pre>';
		
		$query = $db->getQuery(true);
		//$query->select('*');
		$query->select('gesamtTabelle.alias as alias,'.
			' hb_spiel_spieler.spielIdHvw as spielIdHvw,'.
			' hb_spiel_spieler.kuerzel as teamkey,'.
			' hb_spiel_spieler.saison as saison, hb_spiel_spieler.tw as tw,'.
			' hb_mannschaft_spieler.tw as twposition, '.
			'spiele, toregesamt, quote, '.
			'`tore`, `tore7m`, `gelb`, `rot`, `2min1`, `2min2`,'.
			' `2min3`, `groesse`, `geburtstag`, `name`,'.
			' `heim`, `gast`, `toreHeim`, `toreGast`');
		$query->from('('.$innerQuery.') as `spieler`');
		$query->leftJoin($db->qn('hb_spieler').' USING ('.$db->qn('alias').')');
		$query->leftJoin($db->qn('#__contact_details').' USING ('.$db->qn('alias').')');
		$query->leftJoin($db->qn('hb_mannschaft_spieler').' USING ('.$db->qn('alias').')');
		$query->leftJoin($db->qn('hb_spiel_spieler').' ON spieler.alias=hb_spiel_spieler.alias AND spielIDhvw='.$db->q($gameId));
		$query->leftJoin($db->qn('hb_spiel').' USING ('.$db->qn('spielIDhvw').')');
		$query->leftJoin('( '.$totalQuery.' ) as `gesamtTabelle` ON spieler.alias=gesamtTabelle.alias');
		//$query->where($db->qn('trikotNr').' != 0');
		//$query->order($db->qn('datum').' ASC');

		//echo '=> model->$query <br><pre>'; echo $query; echo '</pre>';
		$db->setQuery($query);
		$players = $db->loadObjectList();
		//echo '=> model->players<br><pre>'; print_r($players); echo '</pre>';
		
		//$players = self::addPositions($players);
		return $players;
	}
	
	protected function addPositions($players) {
		
		foreach ($players as $player)
		{
			$positions = array();
			$positionskurz = array();
			
			$positionKeys = array('trainer', 'TW', 'LA', 'RL', 'RM', 'RR', 'RA', 'KM');
			$positionNames = array('Trainer', 'Torwart', 'LinksauÃŸen', 'RÃ¼ckraum-Links',
				'RÃ¼ckraum-Mitte', 'RÃ¼ckraum-Rechts', 'RechtsauÃŸen', 'Kreis');
			$positionAbrv = array('TR', 'TW', 'LA', 'RL', 'RM', 'RR', 'RA', 'KM');
			
			foreach ($positionKeys as $i => $key)
			{
				if ($player->{$key} == true) 
				{
					$positions[] = $positionNames[$i];
					$positionskurz[] = $positionAbrv[$i];
				}
			}
			$player->positions = implode(', ', $positions);
			$player->positionskurz = $positionskurz;
		}
		return $players;
	}
	
	
	
	function getChartGames($teamkey = "non")
	{
		if ($teamkey === "non"){
			$teamkey = $this->teamkey;
		}
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn(array('spielIDhvw','heim','gast')));
		$query->from('hb_spiel');
		$query->where($db->qn('kuerzel').' = '.$db->q($teamkey));
		$query->where('DATE('.$db->qn('datumZeit').') <= '.$db->q($this->gameDate));
		$query->where($db->qn('eigenerVerein').' = 1');
		$query->order($db->qn('datumZeit').' ASC');
		//echo '=> model->$query <br><pre>'.$query.'</pre>';
		$db->setQuery($query);
		$games = $db->loadObjectList();
		//echo '=> model->games <br><pre>'; print_r($games); echo '</pre>';
		return $games;
	}
	
	
	function getChartData($teamkey)
	{
		$games = self::getChartGames($teamkey);
		
		$db = $this->getDbo();
		
		foreach ($games as $key => $game) {
			$query = $db->getQuery(true);
			$query->select('hb_spiel_spieler.alias as alias, name, tore, tore7m, hb_spiel_spieler.tw as tw');
			$query->from('hb_spiel');
			$query->leftJoin($db->qn('hb_spiel_spieler').' USING ('.$db->qn('spielIDhvw').')');
			$query->leftJoin($db->qn('hb_mannschaft_spieler').' ON ( hb_mannschaft_spieler.kuerzel = hb_spiel.kuerzel'.
				' AND hb_mannschaft_spieler.alias = hb_spiel_spieler.alias)');
			$query->leftJoin('#__contact_details ON (#__contact_details.alias = hb_spiel_spieler.alias)');
			$query->where('hb_spiel.'.$db->qn('spielIDhvw').' = '.$db->q($game->spielIDhvw));
			$query->where($db->qn('tore').' IS NOT NULL');
			$query->where('hb_spiel_spieler.'.$db->qn('trikotNr').' != 0');
			$query->group('hb_spiel_spieler.alias');
			$query->order('name');
			//echo '=> model->$query <br><pre>'.$query.'</pre>';
			$db->setQuery($query);
			$players = $db->loadObjectList();
			//echo '=> model->player<br><pre>'; print_r($players); echo '</pre>';
			$gameName = $game->heim.'&'.$game->gast;
			$pattern = '/(.*)&(TSV Geislingen)/';
			$replacement = '${1} (A)';
			$gameName = preg_replace($pattern, $replacement, $gameName);
			$pattern = '/(TSV Geislingen)&(.*)/';
			$replacement = '${2} (H)';
			$gameName = preg_replace($pattern, $replacement, $gameName);
			
			//$gameName = $game->spielIDhvw; 
			$gameIdList[]= $gameName; 
			foreach ($players as $player) {
				$player->tore = (int) $player->tore;
				//$data[str_replace('-','_',$player->alias)][] = 
				$goalkeeper = '';
				if ($player->tw == true) $goalkeeper = 'TW';
				$data[$player->alias][] = 
					array('game' => $gameName, 'goals' => $player->tore, 'penalty' => $player->tore7m, 'name' => $player->name, 'goalie' => $goalkeeper); 
			} 
			foreach ($data as $key => $value) {
				if ($key !== 'game') 
				{
					$prev = 0;
					foreach ($value as $valGame => $valGoal) 
					{
						$data[$key][$valGame]['goalsTotal'] = $valGoal['goals'] + $prev;
						$prev = $valGoal['goals'] + $prev;
					}
				}
			} 
		}
		krsort($data);
		$data['game'] = $gameIdList; 
		//echo '=> model->data<br><pre>'; print_r($data); echo '</pre>';
		return $data;
	}
	
	function getFbMetaTag()
	{
		$title = 'TSV Geislingen - Männer Mannschaft: Torschützen'; //$this->item->title
		$url = JURI::Root().'/index.php/aktive/m-1/m-1goals';
		$image = JURI::Root().'/media/hbteam/images/goalchart-dummy.jpg';
		$desc = 'Statistik der Torschützen dieser Saison';
			
		$fbog = '<meta property="og:site_name" content="TSV Geislingen - Abt. Handball"/>'."\n";
		$fbog .= '<meta property="og:title" content="'.$title.'"/>'."\n";
		$fbog .= '<meta property="og:type" content="article" />'."\n";
		$fbog .= '<meta property="og:image" content="'.$image.'" />'."\n";
		$fbog .= '<meta property="og:url" content="'.$url.'" />'."\n";
		$fbog .= '<meta property="og:description" content="'.$desc.'" />'."\n";
		
		return $fbog;
	}
}