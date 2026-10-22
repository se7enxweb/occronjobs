<?php /*

[NavigationPart]
Part[occronjobnavigationpart]=OC Cronjobs

[Leftmenu_setup]
Links[occronjobs]=cronjobs/show_crontab
LinkNames[occronjobs]=Crontab viewer

[TopAdminMenu]
#Tabs[]=occronjobs

[Topmenu_occronjobs]
# Icon: the name of an icon admin4 draws before the tab's name, from design.ini
# [AdminDesignSettings] IconList[] (Bootstrap Icons); a name it does not know draws no icon.
Icon=clock
NavigationPartIdentifier=occronjobnavigationpart
Name=Cronjobs
Tooltip=Cronjobs dashboard
URL[]
URL[default]=cronjobs/show_crontab
Enabled[]
Enabled[default]=true
Enabled[browse]=false
Enabled[edit]=false
Shown[]
Shown[default]=true
Shown[edit]=true
Shown[navigation]=true
# We don't show it in browse mode
Shown[browse]=true
PolicyList[]=cronjobs/list
*/ ?>
