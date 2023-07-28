/**
 * @file mautic.customFields.js
 * Pushes site and Acquia Personalization info to custom fields in Mautic
 *
 */
(function (Drupal) {

  "use strict";

  /**
   * Adds data to these custom fields in Mautic:
   *  site_url, lift_account, lift_segments
   */
  Drupal.behaviors.mauticCustomFields = {
    attach: function (context) {
      // Add custom field data
      mt('send', 'pageview', {
        site_url: window.location.hostname
      },
        {
          onerror: function () {
            console.log("Mautic - error adding site URL custom field.");
          }
        }
      );

      // Wait until Personalization has finished to add the Personalization segments
      window.addEventListener('acquiaLiftStageCollection', function (e) {
        mt('send', 'pageview', {
          lift_account: AcquiaLift.account_id,
          lift_segments: acquiaLiftSegmentsToString()
        },
          {
            onerror: function () {
              console.log("Mautic - error adding custom field data from Personalization.");
            }
          }
        );
      });  // End eventListener

    }
  };

}(Drupal));

/**
 * A helper function for preparing the Personalization Segments object for a text field.
 */
function acquiaLiftSegmentsToString() {
  let liftSegmentsExist = typeof AcquiaLift.currentSegments === 'object';
  let liftSegments = '';
  if (liftSegmentsExist) {
    // Convert the Personalization Segments from an object to a string of ids
    Object.values(AcquiaLift.currentSegments).forEach(value => {
      liftSegments += value.id + ',';
    });
    // Remove the last comma
    liftSegments = liftSegments.slice(0, -1);
  }

  return liftSegments;
}
