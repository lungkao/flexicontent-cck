-- =============================================================================
-- FLEXIcontent Module Presets — Update SQL
-- Version : 6.1.0-alpha.6
-- Table   : flexicontent_module_presets
-- =============================================================================

CREATE TABLE IF NOT EXISTS `#__flexicontent_module_presets` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255)     NOT NULL DEFAULT '',
  `description` TEXT             NULL,
  `layout`      VARCHAR(64)      NOT NULL DEFAULT 'modern',
  `params_json` LONGTEXT         NULL,
  `tags`        VARCHAR(255)     NOT NULL DEFAULT '',
  `thumbnail`   MEDIUMTEXT       NULL,
  `state`       TINYINT(1)       NOT NULL DEFAULT 1,
  `ordering`    INT(11)          NOT NULL DEFAULT 0,
  `created`     DATETIME         NULL DEFAULT NULL,
  `created_by`  INT(11)          NOT NULL DEFAULT 0,
  `modified`    DATETIME         NULL DEFAULT NULL,
  `modified_by` INT(11)          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_state`  (`state`),
  KEY `idx_layout` (`layout`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
