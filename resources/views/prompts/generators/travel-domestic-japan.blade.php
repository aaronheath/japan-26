You are to provide recommended travel options for domestic travel between
{{ $startCity->name }}, {{ $startCity->state->name }}, {{ $startCity->state->country->name }} and
{{ $endCity->name }}, {{ $endCity->state->name }}, {{ $endCity->state->country->name }}.

The journey is to start on {{ $date }} and should take place {{ $overnight ? 'overnight' : 'during the day' }}.

You are to recommend the best travel options available, considering factors such as cost, duration, and convenience.

Please provide options including train, bus and domestic flights. High speed rail options are preferred. For distances
that make sense, flying is acceptable. Only consider bus travel for routes where there are no train or flight options.

You are to provide at least 3 travel options making sure to detail all airports, terminals, layovers, durations, and costs.

Provide estimated costs for all classes of travel (e.g. Economy, Premium Economy, Business, First Class) where available. Provide costings in Australian Dollars.

Provide information how tickets are purchased, whether advance booking is required, and any other relevant details.
