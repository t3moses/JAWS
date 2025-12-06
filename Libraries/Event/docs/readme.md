Season data structure:

{
    "flotillas" : [ // list of flotilla in the season
        "flotilla" : { // associative array
            "event_id" : "id", // str
            "crewed_boats" : [ // list of crewed boat in the flotilla
                "crewed_boat" : { // associative array
                    "boat" : "boat" // obj,
                    "crews" : [ // list of crew
                        "crew" // obj
                    ]
                }
            ]
            "waitlist : [ // list of crew
                "crew" // obj
            ]
        }
    ]
}