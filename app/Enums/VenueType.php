<?php

namespace App\Enums;

enum VenueType: string
{
    case Hotel = 'hotel';
    case Restaurant = 'restaurant';
    case Wrestling = 'wrestling';
    case Airport = 'airport';
    case TrainStation = 'train_station';
    case Attraction = 'attraction';
    case Other = 'other';
}
