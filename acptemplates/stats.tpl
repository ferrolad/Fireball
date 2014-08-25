{include file='header' pageTitle='cms.acp.stats'}
<header class="boxHeadline">
	<h1>{lang}cms.acp.stats{/lang}</h1>
</header>

<div class="contentNavigation">
	<form method="post" action="{link application='cms' controller='Stats'}{/link}">
		<input type="date" name="startDate" value="{$startDate|date}"/>
		<input type="date" name="endDate" value="{$endDate|date}"/>
		<input type="submit" />
	</form>
</div>
		<div  class="container containerPadding shadow marginTop">
		<fieldset>
			<legend>{lang}cms.acp.stats.vistors{/lang}</legend>
			<div class="center">
				<div id="canvas" style="height: 250px;"></div>
			</div>
		</fieldset>
		</div>
		<div class="container containerPadding shadow marginTop" style="float: left; width: 49%; box-sizing: border-box; margin-right: 1%;">
			<fieldset>
			<legend>{lang}cms.acp.stats.browsers{/lang}</legend>
			<div class="center">
				<div id="browsers"></div>
			</div>
		</fieldset>
		</div>
		<div class="container containerPadding shadow marginTop" style="float: left; width: 49%; box-sizing: border-box; margin-right: 1%;">
			<fieldset>
			<legend>{lang}cms.acp.stats.platforms{/lang}</legend>
			<div class="center">
				<div id="platforms"></div>
			</div>
		</fieldset>
		</div>

		<br style="clear: both;" />
		<div class="container containerPadding shadow marginTop" style="float: left; width: 49%; box-sizing: border-box; margin-right: 1%;">
			<fieldset>
			<legend>{lang}cms.acp.stats.devices{/lang}</legend>
			<div class="center">
				<div id="devices"></div>
			</div>
		</fieldset>
		</div>
		<div class="container containerPadding shadow marginTop clearfix" style="float: left; width: 49%; box-sizing: border-box; margin-left: 1%;">
			<fieldset>
			<legend>{lang}cms.acp.stats.mostClicked{/lang}</legend>
			<table class="table">
				<thead>
					<tr>
						<th>{lang}cms.acp.stats.page{/lang}</th>
						<th>{lang}cms.acp.stats.clicks{/lang}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$pages item=page}
						<tr>
							<td>{$page->getTitle()|language}</td>
							<td>{$page->clicks}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
			</fieldset>
		</div>
		<br style="clear: both;" />
	<script data-relocate="true" src="https://www.google.com/jsapi"></script>
	<script data-relocate="true">
	{literal}google.load("visualization", "1", {packages:["corechart"]});{/literal}
	google.setOnLoadCallback(drawArea);
	function drawArea() {
		var data = google.visualization.arrayToDataTable([
		['Visits', '{lang}cms.acp.dashboard.all{/lang}', '{lang}cms.acp.dashboard.registered{/lang}', '{lang}cms.acp.dashboard.spiders{/lang}'],
		{foreach from=$visits item=visit}
		['{$visit['string']}', {if $visit['visitors']['visits']|isset}{$visit['visitors']['visits']}{else}0{/if}, {if $visit['visitors']['users']|isset}{$visit['visitors']['users']}{else}0{/if}, {if $visit['visitors']['spiders']|isset}{$visit['visitors']['spiders']}{else}0{/if}],
		{/foreach}
		]);

		var options = {
		title: '',
		backgroundColor: 'transparent',
		fontName: 'Trebuchet MS',
		{literal}
		legend: {position: 'bottom'},
		vAxis: {minValue: 0}
		{/literal}
		};

		var chart = new google.visualization.AreaChart(document.getElementById('canvas'));
		chart.draw(data, options);
	}
		google.setOnLoadCallback(drawChart);
		function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Browser', 'Visits'],
				{foreach from=$browsers item=browser key=name}
				['{$name}', {$browser['visits']}],
				{/foreach}
			]);

			var options = {
			title: '',
			backgroundColor: 'transparent',
			fontName: 'Trebuchet MS',
			is3D: true
			};

			var chart = new google.visualization.PieChart(document.getElementById('browsers'));
			chart.draw(data, options);
		}

		google.setOnLoadCallback(drawPlatformChart);
		function drawPlatformChart() {
			var data = google.visualization.arrayToDataTable([
				['Platform', 'Visits'],
				{foreach from=$platforms item=platform key=name}
				['{$name}', {$platform['visits']}],
				{/foreach}
			]);

			var options = {
			title: '',
			backgroundColor: 'transparent',
			fontName: 'Trebuchet MS',
			is3D: true
			};

			var chart = new google.visualization.PieChart(document.getElementById('platforms'));
			chart.draw(data, options);
		}

		google.setOnLoadCallback(drawDeviceChart);
		function drawDeviceChart() {
			var data = google.visualization.arrayToDataTable([
				['Device', 'Visits'],
				{foreach from=$devices item=device key=name}
				['cms.acp.stats.devices.{$name}', {$device['visits']}],
				{/foreach}
			]);

			var options = {
			title: '',
			backgroundColor: 'transparent',
			fontName: 'Trebuchet MS',
			pieHole: 0.4
			};

			var chart = new google.visualization.PieChart(document.getElementById('devices'));
			chart.draw(data, options);
		}
	</script>

	<div class="container marginTop">
		<ol class="containerList infoBoxList">
			<li class="box32">
				<span class="icon icon32 icon-user"></span>
				<div class="containerHeadline">
					<h3>{lang}cms.acp.stats.userOnline{/lang}</h3>
				</div>
				<ul class="containerBoxList doubleColumned">
					{foreach from=$objects item=user}
						{if $user->userID}
							<li>
								<span class="icon icon16 icon-user"></span>
								<a href="{link controller='User' object=$user forceFrontend=true}{/link}">{@$user->getFormattedUsername()}</a>
								-
								<small>{@$user->lastActivityTime|time}</small>
								-
								<small>{$user->getBrowser()}</small>
							</li>
						{elseif $user->spiderID}
							<li>
								<span class="icon icon16 icon-bullseye"></span>
								{$user->getSpider()->spiderName}
								-
								<small>{@$user->lastActivityTime|time}</small>
								-
								<small>{$user->getBrowser()}</small>
							</li>
						{else}
							<li>
								<span class="icon icon16 icon-question-sign"></span>
								{lang}wcf.user.guest{/lang}
								-
								<small>{@$user->lastActivityTime|time}</small>
								-
								<small>{$user->getBrowser()}</small>
							</li>
						{/if}
					{/foreach}
				</ul>
			</li>
		</ol>
	</div>
{include file='footer'}
