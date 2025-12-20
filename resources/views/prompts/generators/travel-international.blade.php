You are to provide recommended travel options for international travel between
{{ $startCity->name }}, {{ $startCity->state->name }}, {{ $startCity->state->country->name }} and
{{ $endCity->name }}, {{ $endCity->state->name }}, {{ $endCity->state->country->name }}.

The journey is to start on {{ $date }} and should take place {{ $overnight ? 'overnight' : 'during the day' }}.

You are to recommend the best travel options available, considering factors such as cost, duration, and convenience.

Direct flight or minimal layovers are preferred however where necessary more premium airlines are preferred.

The person taking the travel is a Qantas Club member meaning that they are eligible for certain benefits and upgrades.
Because of this you are to prioritize airlines and routes that offer Qantas Club benefits. Where Qantas is not possible,
airlines or airports that offer single flight lounge access should be prirotied.

You are to provide at least 3 travel options making sure to detail all airports, terminals, layovers, durations, and costs.

Where a destination has multiple airports, you should prioritize the most convenient airport for the traveler. The
traveller priorities easy transfers. No special consideration has to be made for excess baggage or special assistance.

Provide estimated costs for all classes of travel (e.g. Economy, Premium Economy, Business, First Class) where available. Provide costings in Australian Dollars.
