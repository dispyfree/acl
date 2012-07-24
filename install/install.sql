

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_aco`
--

CREATE TABLE IF NOT EXISTS `tbl_aco` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `path` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aco_collection_id` (`collection_id`),
  KEY `path` (`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_aco_collection`
--

CREATE TABLE IF NOT EXISTS `tbl_aco_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(20) NOT NULL,
  `model` varchar(15) NOT NULL,
  `foreign_key` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_action`
--

CREATE TABLE IF NOT EXISTS `tbl_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tbl_action`
--

INSERT INTO `tbl_action` (`id`, `name`, `created`) VALUES
(5, 'create', 0),
(6, 'read', 0),
(7, 'update', 0),
(8, 'delete', 0),
(9, 'grant', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_aro`
--

CREATE TABLE IF NOT EXISTS `tbl_aro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `path` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aco_collection_id` (`collection_id`),
  KEY `path` (`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tbl_aro`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_aro_collection`
--

CREATE TABLE IF NOT EXISTS `tbl_aro_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(20) NOT NULL,
  `model` varchar(15) NOT NULL,
  `foreign_key` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_permission`
--

CREATE TABLE IF NOT EXISTS `tbl_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aco_id` int(11) NOT NULL,
  `aro_id` int(11) NOT NULL,
  `aco_path` varchar(11) NOT NULL,
  `aro_path` varchar(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aco_id` (`aco_id`,`aro_id`,`aco_path`,`aro_path`),
  KEY `action_id` (`action_id`),
  KEY `created` (`created`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tbl_permission`
--