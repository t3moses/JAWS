
The .ical standard is desribed in RFC5545

eluceo/ical is described here ...

https://ical.poerschke.nrw/docs/

PUBLISH and CANCEL methods are set at the VCALENDAR level.  Two VCALENDARs in one file is unreliable; the calendar application may only process one of them.  Two files in one download is unreliable; the calendar application may only process one of them.

Events are identified by UID, which includes the boat's or crew's display name.  This is used by the calendar application to correlate event updates.

The SEQUENCE number must increase monotonically.  An update with a lower sequence number is ignored by the calendar application.

eluceo/ical 2.13 does not include setters for METHOD and SEQUENCE.  So, these have to added to the file before saving it.
