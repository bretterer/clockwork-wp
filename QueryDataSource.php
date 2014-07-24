<?php
class QueryDataSource extends DataSource
{
protected $context;

/**
* YourAppContext should link to your main web-app
* and fetch timings, queries, and logs
*/
function __construct(YourAppContext $context)
{
$this->context = $context;
}

/**
* the entry-point. called by Clockwork itself.
*/
function resolve(Request $request)
{
$timings = $this->context->getTimings();

// optionally: pre-sort the timeline
uasort($timeline, function($a, $b) {
if($a['start'] > $b['start'])
return 1;

if($a['start'] == $b['start']) {
if($a['end'] > $b['end'])
return 1;
elseif ($a['end'] < $b['end'])
return -1;

return 0;
}

return -1;
});

$queries = $this->context->getQueries();

$request->timelineData = $timeline;
$request->databaseQueries = $queries;

return $request;
}
}