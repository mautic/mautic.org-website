#!/bin/sh
SCRIPT_DIR="$(cd -- "$(dirname "$0")"; pwd -P)"

DRUSH="$SCRIPT_DIR/vendor/bin/drush"
DOCROOT="$SCRIPT_DIR/docroot"
COMMAND_RESULT=0

cd $DOCROOT

# Enable maintenance mode
${DRUSH} sset system.maintenance_mode 1 --input-format=integer

# Update site
${DRUSH} updb -y
COMMAND_RESULT=$(( $COMMAND_RESULT +$?))

# Enable maintenance mode again, as updb always set it to false after running
# if it ran an update
${DRUSH} sset system.maintenance_mode 1 --input-format=integer

# Clear caches
${DRUSH} cr
COMMAND_RESULT=$(( $COMMAND_RESULT +$?))

# Import configuration overrides if there is any config yet.
CONFIG_DIR=$(${DRUSH} st --field=config-sync)
if [ -d $CONFIG_DIR ] && [ 0 -lt $(ls ${CONFIG_DIR}/*.yml 2>/dev/null | wc -w) ]; then
  # Ensure config_import_single is enabled
  ${DRUSH} en config_import_single, config_ignore, config, config_split -y
  # Ensure the ignored config is up to date.
  ${DRUSH} config_import_single:single-import ../config/sync/config_ignore.settings.yml
  ${DRUSH} config_import_single:single-import ../config/sync/config_split.config_split.whitelist.yml
  # Ensure CMI knows about the split.
  ${DRUSH} config_import_single:single-import ../config/sync/config_split.config_split.[[ environment_name ]].yml
  ${DRUSH} cr
  COMMAND_RESULT=$(( $COMMAND_RESULT +$?))
  # Single cim should work without issues
  ${DRUSH} cim -y
  COMMAND_RESULT=$(( $COMMAND_RESULT +$?))
else
  echo "Skipping config import, as no config exists yet."
fi

# Run deploys hooks.
${DRUSH} deploy:hook -y

# Clear caches
${DRUSH} cr
COMMAND_RESULT=$(( $COMMAND_RESULT +$?))

# Run cron (Enable when your project needs it on build).
# ${DRUSH} cron

# Clear Varnish cache last
${DRUSH} cre -y

# Turn maintenance mode off
${DRUSH} sset system.maintenance_mode 0 --input-format=integer
exit $COMMAND_RESULT
