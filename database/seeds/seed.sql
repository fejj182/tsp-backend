insert into routes (route_id, service_name)
select route_id, route_short_name from renfe_horarios.routes;

insert into journeys (journey_id, route_id, journey_id_full)
select trip_id, route_id, service_id from renfe_horarios.trips;

insert into stops (station_id, arrival_time, departure_time, stop_sequence, journey_id)
select stop_id, arrival_time, departure_time, stop_sequence, trip_id
from renfe_horarios.stop_times;