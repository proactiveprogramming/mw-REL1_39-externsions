CREATE TABLE /*_*/ba_campaign (
  `id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50),
  `adset_id` int(10),
  `start_date` int(10),
  `end_date` int(10)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ba_campaign_index ON /*_*/ba_campaign (id);

CREATE TABLE /*_*/ba_campaign_pages (
  `id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `camp_id` int(10),
  `page_id` int(10)
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ba_campaign_pages_index ON /*_*/ba_campaign_pages (id);

CREATE TABLE /*_*/ba_adset (
  `id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50)
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ba_adset_index ON /*_*/ba_adset (id);

CREATE TABLE /*_*/ba_ad (
  `id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `adset_id` int(10),
  `name` varchar(50),
  `ad_type` int(1),
  `ad_img_url` varchar(250),
  `ad_url` varchar(250)
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ba_ad_index ON /*_*/ba_ad (id);

CREATE TABLE /*_*/ba_ad_stats (
  `id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `camp_id` int(10),
  `ad_id` int(10),
  `page_id` int(10),
  `counter` int(10)
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ba_ad_stats_index ON /*_*/ba_ad_stats (id);
