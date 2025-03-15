(function($) {
    'use strict';
    
    const selectors = {
        countrySelect: $('#prayer_country_id'),
        citySelect: $('#prayer_city_id'),
        districtSelect: $('#prayer_district_id'),
        timezoneCheckbox: $('#prayer_use_custom_timezone'),
        timezoneSelect: $('#prayer_custom_timezone'),
        placeIdDisplay: $('#place_id_display'),
        placeIdInput: $('#prayer_place_id'),
        timezoneInput: $('.custom-timezone-input'),
        submitButton: $('input[type="submit"]'),
        settingsForm: $('.prayer-settings-form'),
        languageSelect: $('#prayer_language')
    };
    
    const initialValues = {
        country: '',
        city: '',
        district: '',
        useCustomTimezone: selectors.timezoneCheckbox.prop('checked') ? '1' : '0',
        customTimezone: selectors.timezoneSelect.val()
    };
    
    let currentValues = {
        country: '',
        city: '',
        district: ''
    };
    
    function checkFormChanges() {
        let changed = false;
        
        if (selectors.countrySelect.val() !== initialValues.country ||
            selectors.citySelect.val() !== initialValues.city ||
            selectors.districtSelect.val() !== initialValues.district) {
            changed = true;
        }
        
        const useCustom = selectors.timezoneCheckbox.prop('checked') ? '1' : '0';
        if (useCustom !== initialValues.useCustomTimezone ||
            selectors.timezoneSelect.val() !== initialValues.customTimezone) {
            changed = true;
        }
        
        selectors.submitButton.prop('disabled', !changed);
        return changed;
    }
    
    function loadCountries() {
        currentValues = {
            country: '',
            city: '',
            district: ''
        };
        
        selectors.citySelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
        selectors.districtSelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
        selectors.placeIdDisplay.text('');
        selectors.placeIdInput.val('');
        
        return $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: { 
                action: "get_countries",
                nonce: prayer_ajax.nonce,
                nocache: new Date().getTime()
            },
            cache: false,
            beforeSend: () => {
                selectors.countrySelect.prop('disabled', true).html('<option>' + prayer_ajax.translations.loading + '</option>');
            }
        }).done((response) => {
            selectors.countrySelect.html(response).prop('disabled', false);
            
            $.ajax({
                url: prayer_ajax.ajax_url,
                method: "POST",
                data: {
                    action: "get_saved_location",
                    nonce: prayer_ajax.nonce
                }
            }).done((savedLocation) => {
                if (savedLocation.country) {
                    currentValues.country = savedLocation.country;
                    selectors.countrySelect.val(savedLocation.country);
                    loadCities(savedLocation.country).then(() => {
                        if (savedLocation.city) {
                            currentValues.city = savedLocation.city;
                            selectors.citySelect.val(savedLocation.city);
                            loadDistricts(savedLocation.country, savedLocation.city).then(() => {
                                if (savedLocation.district) {
                                    currentValues.district = savedLocation.district;
                                    selectors.districtSelect.val(savedLocation.district);
                                    loadPlaceID();
                                }
                            });
                        }
                    });
                }
            });
            
            checkFormChanges();
        }).fail(() => {
            selectors.countrySelect.html('<option>An error occurred</option>').prop('disabled', false);
        });
    }
    
    function loadCities(country) {
        if (!country) {
            selectors.citySelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
            selectors.districtSelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
            return $.Deferred().resolve().promise();
        }
        
        return $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: { 
                action: "get_cities",
                country: country,
                nonce: prayer_ajax.nonce,
                nocache: new Date().getTime()
            },
            cache: false,
            beforeSend: () => {
                selectors.citySelect.prop('disabled', true).html('<option>' + prayer_ajax.translations.loading + '</option>');
            }
        }).done((response) => {
            selectors.citySelect.html(response).prop('disabled', false);
            checkFormChanges();
        }).fail(() => {
            selectors.citySelect.html('<option>Error loading cities</option>').prop('disabled', false);
        });
    }
    
    function loadDistricts(country, city) {
        if (!country || !city) {
            selectors.districtSelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
            return $.Deferred().resolve().promise();
        }
        
        return $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: { 
                action: "get_districts",
                country: country,
                city: city,
                nonce: prayer_ajax.nonce,
                nocache: new Date().getTime()
            },
            cache: false,
            beforeSend: () => {
                selectors.districtSelect.prop('disabled', true).html('<option>' + prayer_ajax.translations.loading + '</option>');
            }
        }).done((response) => {
            selectors.districtSelect.html(response).prop('disabled', false);
            checkFormChanges();
        }).fail(() => {
            selectors.districtSelect.html('<option>Error loading districts</option>').prop('disabled', false);
        });
    }
    
    function loadPlaceID() {
        if (!currentValues.country || !currentValues.city || !currentValues.district) {
            selectors.placeIdDisplay.text('Please make a selection...');
            selectors.placeIdInput.val('');
            return;
        }
        
        $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: { 
                action: "get_place_id",
                country: currentValues.country,
                city: currentValues.city,
                district: currentValues.district,
                nonce: prayer_ajax.nonce
            },
            beforeSend: () => {
                selectors.placeIdDisplay.text(prayer_ajax.translations.loading);
            }
        }).done((response) => {
            selectors.placeIdDisplay.text('Place ID: ' + response);
            selectors.placeIdInput.val(response);
            checkFormChanges();
        }).fail(() => {
            selectors.placeIdDisplay.text('Error retrieving Place ID');
        });
    }
    
    function init() {
        $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: {
                action: "check_welcome_popup",
                nonce: prayer_ajax.nonce
            },
            success: function(response) {
                if (response === 'show') {
                    showWelcomePopup();
                }
            }
        });

        selectors.submitButton.prop('disabled', true);
        
        selectors.countrySelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
        selectors.citySelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
        selectors.districtSelect.html('<option value="">' + prayer_ajax.translations.loading + '</option>').prop('disabled', true);
        selectors.placeIdDisplay.text('');
        selectors.placeIdInput.val('');
        
        loadCountries().then(() => {
            if (currentValues.country) {
                return loadCities(currentValues.country);
            }
        }).then(() => {
            if (currentValues.country && currentValues.city) {
                return loadDistricts(currentValues.country, currentValues.city);
            }
        }).then(() => {
            if (currentValues.country && currentValues.city && currentValues.district) {
                loadPlaceID();
            }
        });
        
        selectors.countrySelect.on('change', function() {
            currentValues.country = $(this).val();
            currentValues.city = '';
            currentValues.district = '';
            
            selectors.citySelect.html('<option value="">Choose City</option>').prop('disabled', true);
            selectors.districtSelect.html('<option value="">Choose District</option>').prop('disabled', true);
            selectors.placeIdDisplay.text('Please make a selection...');
            selectors.placeIdInput.val('');
            
            loadCities(currentValues.country);
            checkFormChanges();
        });
        
        selectors.citySelect.on('change', function() {
            currentValues.city = $(this).val();
            currentValues.district = '';
            
            selectors.districtSelect.html('<option value="">Choose District</option>').prop('disabled', true);
            selectors.placeIdDisplay.text('Please make a selection...');
            selectors.placeIdInput.val('');
            
            loadDistricts(currentValues.country, currentValues.city);
            checkFormChanges();
        });
        
        selectors.districtSelect.on('change', function() {
            currentValues.district = $(this).val();
            loadPlaceID();
            checkFormChanges();
        });
        
        selectors.timezoneCheckbox.on('change', function() {
            selectors.timezoneInput.slideToggle(300);
            checkFormChanges();
        });
        
        selectors.timezoneSelect.on('change', checkFormChanges);
        
        selectors.settingsForm.on('submit', function(e) {
            if (!checkFormChanges()) {
                e.preventDefault();
                return false;
            }
        });
        
        selectors.languageSelect.on('change', function() {
            var selectedLanguage = $(this).val();
            
            $('form.prayer-settings-form').submit();
        });
    }
    
    $(document).ready(init);
    
})(jQuery);

function copyIban(button) {
    const iban = button.previousElementSibling.textContent;
    navigator.clipboard.writeText(iban).then(() => {
        showToast(prayer_ajax.translations.iban_copied);
    });
}

function copyName(button) {
    const name = button.previousElementSibling.textContent;
    navigator.clipboard.writeText(name).then(() => {
        showToast(prayer_ajax.translations.name_copied);
    });
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'copy-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    toast.offsetWidth;
    
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

function updateDesignPreview(prayerTime) {
    const gradients = {
        'İmsak': 'linear-gradient(135deg, #141E30, #243B55)',
        'Güneş': 'linear-gradient(135deg, #FF512F, #F09819)',
        'Öğle': 'linear-gradient(135deg, #2193b0, #6dd5ed)',
        'İkindi': 'linear-gradient(135deg, #FFB75E, #ED8F03)',
        'Akşam': 'linear-gradient(135deg, #4B1248, #F0C27B)',
        'Yatsı': 'linear-gradient(135deg, #0F2027, #203A43)',
        'Fajr': 'linear-gradient(135deg, #141E30, #243B55)',
        'Sunrise': 'linear-gradient(135deg, #FF512F, #F09819)',
        'Dhuhr': 'linear-gradient(135deg, #2193b0, #6dd5ed)',
        'Asr': 'linear-gradient(135deg, #FFB75E, #ED8F03)',
        'Maghrib': 'linear-gradient(135deg, #4B1248, #F0C27B)',
        'Isha': 'linear-gradient(135deg, #0F2027, #203A43)'
    };

    const widgets = document.querySelectorAll('.prayer-widget');
    const tables = document.querySelectorAll('.prayer-times-container');
    const banners = document.querySelectorAll('.prayer-banner');

    const selectedGradient = gradients[prayerTime] || gradients['Fajr'];
    
    widgets.forEach(widget => {
        widget.style.background = selectedGradient;
    });
    
    tables.forEach(table => {
        table.style.background = selectedGradient;
    });
    
    banners.forEach(banner => {
        banner.style.background = selectedGradient;
    });

    document.querySelectorAll('.prayer-time').forEach(item => {
        const prayerName = item.getAttribute('data-prayer');
        item.classList.remove('active-time');
        
        if (prayerName === prayerTime) {
            item.classList.add('active-time');
        }
    });
    
    document.querySelectorAll('.prayer-time-item, .prayer-banner-time').forEach(item => {
        item.classList.remove('active');
        const label = item.querySelector('.prayer-time-label, .prayer-label');
        if (label && label.textContent.trim() === prayerTime) {
            item.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.prayer-time-selector').forEach(selector => {
        selector.addEventListener('change', function() {
            updateDesignPreview(this.value);
        });
        
        if (selector.value) {
            updateDesignPreview(selector.value);
        }
    });

    const tabs = document.querySelectorAll('.shortcode-tab');
    const contents = document.querySelectorAll('.shortcode-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            contents.forEach(content => {
                content.classList.remove('active');
                if (content.id === `${target}-content`) {
                    content.classList.add('active');
                }
            });
        });
    });
});

function showWelcomePopup() {
    const popup = document.getElementById('welcomePopup');
    if (popup) {
        popup.classList.add('active');
    }
}

function closeWelcomePopup() {
    const popup = document.getElementById('welcomePopup');
    if (popup) {
        popup.classList.remove('active');
        $.ajax({
            url: prayer_ajax.ajax_url,
            method: "POST",
            data: {
                action: "hide_welcome_popup",
                nonce: prayer_ajax.nonce
            }
        });
    }
}