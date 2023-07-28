Dropsolid Purger

The Dropsolid Purger module enables you to invalidate caches in multiple varnish loadbalancers. 

This module is a rework of the Acquia purge module to be usable on every environment. 

Install instructions:


1. Install the module as usual
2. Go to /admin/config/development/performance/purge
3. Add the Dropsolid Varnish purge purger 
4. Add the configuration to your settings.php file

<pre>
$config['dropsolid_purge.config']= [
  'site_name' => "Somename",
  'site_environment' => "local",
  'site_group' => "DropsolidSolutions",
  'loadbalancers' => [
    'varnish' => [
      'ip' => '127.0.0.1',
      'protocol' => 'http',
      'port' => '8080'
    ],
  ]
];
</pre>

<small>The loadbalancer configuration consists of the public ip/protocol/port varnish listens to. 
The ip is required the protocol will fallback to http and the port will default to port 80</small>

We recommend using both cron and lateruntime purge processor for optimal invalidation speed. 

Features:

1. Currently the Dropsolid Purge module supports tag invalidation and everything () invalidation. 

2. The module will only purge tags for the current site by using the X-Dropsolid-Site header. 
The current site is defined by the name you set in config and the subsite directory.

3. The purge module supports multiple loadbalancers

4. There is also a default vcl in the examples folder that contains the logic for the bans


Troubleshooting

Imagecache
- We've seen cases where an empty image style gets cached. It is because Drupal actually gives the response code 200
 if something goes wrong. It should give a 500 as response. To do this apply the patch in this thread: https://www.drupal.org/project/drupal/issues/2688999#comment-11104897   