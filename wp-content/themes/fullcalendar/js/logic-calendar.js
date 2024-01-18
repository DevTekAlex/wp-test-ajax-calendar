document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: function(info, successCallback, failureCallback) {
            fetch(scriptParams.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_events'
            })
                .then(response => response.json())
                .then(events => successCallback(events))
                .catch(error => failureCallback(error));
        },
        eventClick: function(info) {
            var eventId = info.event.id;

            fetch(scriptParams.ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_event_details&event_id=' + eventId
            })
                .then(response => response.json())
                .then(eventData => {
                    showEventModal(eventData);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });
    calendar.render();
});

function showEventModal(eventData) {
    var modalContent = `<div class="modal-content">
                                    <div id="closeModal">X</div>
                                    <div class="title"><h2>${eventData.title}</h2></div>
                                    <div>Event start: ${eventData.start}</div>
                                    <div>Event end: ${eventData.end}</div>
                                    <div><img src="${eventData.img}" alt="${eventData.title}"></div>
                                    <form id="leadForm">
                                        <input type="text" name="first_name" placeholder="First Name">
                                        <input type="text" name="last_name" placeholder="Last Name">
                                        <input type="email" name="email" placeholder="Email">
                                        <input type="tel" name="phone" placeholder="Phone">
                                        <input type="hidden" name="event_title" value="${eventData.title}">
                                        <button type="submit">Submit</button>
                                    </form>
                                    <div id="formResult"></div>
                                </div>`;

    var modal = document.createElement('div');
    modal.className = 'modal-body';
    modal.innerHTML = modalContent;
    document.body.appendChild(modal);

    document.getElementById('closeModal').addEventListener('click', function() {
        modal.remove();
    });

    document.getElementById('leadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'add_lead_from_form');
        formData.append('add_lead_nonce', scriptParams.nonce);

        fetch(scriptParams.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('leadForm').style.display = 'none';
                    document.getElementById('formResult').textContent = 'You have successfully registered for the event!';
                } else {

                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // console.log(eventData);
}