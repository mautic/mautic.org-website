(function(w,d,t,drupalSettings,n,a,m){w['MauticTrackingObject']=n;
w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
m=d.getElementsByTagName(t)[0];a.async=1;a.src=drupalSettings.mautic.base_url;m.parentNode.insertBefore(a,m)
})(window,document,'script',drupalSettings,'mt');
mt('send', 'pageview');
