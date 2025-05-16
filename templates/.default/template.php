<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var CustomRegistrationComponent $component */

\Bitrix\Main\UI\Extension::load(['ui.vue3', 'ui.notification']);
\Bitrix\Main\Loader::includeModule('iblock');
$arPositions = [];
$arSelect = array("ID", "NAME", "DATE_ACTIVE_FROM");
$arFilter = array("IBLOCK_ID" => 17, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
$res = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $arPositions[] = $arFields["NAME"];
}
$arCity = [];
$arSelect = array("ID", "NAME", "DATE_ACTIVE_FROM");
$arFilter = array("IBLOCK_ID" => 19, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
$res = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $arCity[] = $arFields["NAME"];
}
?>
<section class="brand-showcase">
    <div class="brand-showcase__container">
        <div class="brand-showcase__content">
            <h2>Станьте частью <br> команды ME! </h2>
            <p class="text-16">Уже есть аккаунт? <b><a href="/login/">Войти</a></b></p>
            <div id="registration-app">
                <!-- Основная форма регистрации -->
                <form v-if="!showCodeVerification" @submit.prevent="submitForm" enctype="multipart/form-data" method="POST">
                    <div class="input-block input-block__dubl">
                        <div>
                            <input v-model="formData.lastName" autocomplete="off" type="text" name="name" placeholder="Фамилия" class="input-main text-14-2">
                        </div>
                        <div>
                            <input v-model="formData.firstName" autocomplete="off" type="text" name="familia" placeholder="Имя" class="input-main text-14-2">
                        </div>
                    </div>
                    <div class="input-block input-block__dubl">
                        <div>
                            <label for="tel" class="Text-12-3 label-color">Телефон</label>
                            <div class="input-content">
                                <input v-model="formData.phone" type="tel" name="tel" placeholder="+7 (___) ___-__-__" class="input-main text-14-2" @input="formatPhone" @keydown="phoneKeyDown" @beforeinput="handleBeforeInput">
                                <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
                            </div>
                        </div>
                        <div>
                            <label for="email" class="Text-12-3 label-color">Email</label>
                            <div class="input-content">
                                <input v-model="formData.email" type="email" name="email" placeholder="Ваша почта" class="input-main text-14-2">
                                <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
                            </div>
                        </div>
                    </div>
                    <label class="select-input _icon-Icon-24-Down">
                        <div class="selected input-main text-14-2">{{ formData.position || 'Должность' }}</div>
                        <ul class="options">
                            <li v-for="(option, index) in positions" :key="index" @click="selectPosition(option)" class="text-14-2">{{ option }}</li>
                        </ul>
                        <input type="hidden" name="form[position]" v-model="formData.position" class="select-value">
                    </label>
                    <label class="select-input _icon-Icon-24-Down">
                        <div class="selected input-main text-14-2">{{ formData.city || 'Город' }}</div>
                        <ul class="options">
                            <li v-for="(option, index) in cities" :key="index" @click="selectCity(option)" class="text-14-2">{{ option }}</li>
                        </ul>
                        <input type="hidden" name="form[city]" v-model="formData.city" class="select-value">
                    </label>
                    <label>
                        <div class="input-main input-file _icon-Icon-24-upload">
                            <input
                                    type="file"
                                    @change="handleFileUpload"
                                    name="educationCertificate"
                                    accept=".pdf,.doc,.docx,.jpg,.png"
                            >
                            <p class="text-14-2">
                                <span v-if="!fileUploaded">Загрузить свидетельство о проф. образовании</span>
                                <span v-else class="file-uploaded">{{ fileName }} ✓</span>
                            </p>
                        </div>
                    </label>
                    <label class="custom-checkbox">
                        <div>
                            <input v-model="formData.privacyPolicy" type="checkbox" name="politika" class="checkbox-input">
                            <div class="checkbox-icon _icon-Icon-16-check"></div>
                            Я согласен с <a href="#">политикой конфиденциальности</a>
                        </div>
                        <span v-if="errors.privacyPolicy" class="error-text">{{ errors.privacyPolicy }}</span>
                    </label>
                    <label class="custom-checkbox">
                        <div>
                            <input v-model="formData.newsletter" type="checkbox" name="yvedom" class="checkbox-input">
                            <div class="checkbox-icon _icon-Icon-16-check"></div>
                            Я хочу узнавать о специальных предложениях и новостях
                        </div>
                        <span v-if="errors.newsletter" class="error-text">{{ errors.newsletter }}</span>
                    </label>
                    <button type="submit" class="button text-button button-text-14">Зарегистрироваться</button>
                </form>

                <!-- Форма для ввода кода подтверждения -->
                <form v-if="showCodeVerification" @submit.prevent="verifyCode" class="code-verification-form">
                    <p class="text-20">Код подтверждения</p>
                    <div class="input-block code-input">
                        <div class="code-container">
                            <input v-for="index in 6" :key="index" v-model="code[index - 1]" type="text" maxlength="1" name="code" class="code-box text-18 input-main input-code only-numbers" inputmode="numeric" @input="focusNext(index)">
                        </div>
                    </div>
                    <p class="text-20">Отправить повторно через <span class="text-20 timer-countdown">{{ timer }}</span></p>
                    <button type="submit" class="button text-button button-text-14">Войти</button>
                </form>

                <div v-if="message" :class="['message', messageClass]">{{ message }}</div>
            </div>
        </div>
        <div class="brand-showcase__preview _2">
            <img src="<?= SITE_TEMPLATE_PATH?>/img/preview-2.png" alt="">
            <div class="">
                <h1><span class="h1-40-2">немецкое</span> качество
                    с французским <span class="h1-40-2">шиком</span></h1>
                <p class="text-18">Бренд профессиональной косметики для волос из Германии</p>
                <a href="/#about-brand" type="submit" class="button text-button button-white button-text-14 ">Подробнее</a>
            </div>
        </div>
    </div>
</section>

<script>
    BX.ready(function() {
        const { createApp, ref, computed, onMounted } = BX.Vue3;

        const app = createApp({
            setup() {
                const formData = ref({
                    lastName: '',
                    firstName: '',
                    phone: '',
                    email: '',
                    position: '',
                    city: '',
                    educationCertificate: null,
                    privacyPolicy: false,
                    newsletter: false
                });

                const positions = ref(<?= json_encode($arPositions, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT) ?>);
                const cities = ref(<?= json_encode($arCity, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT) ?>);
                const errors = ref({});
                const message = ref('');
                const messageClass = ref('');
                const showCodeVerification = ref(false);
                const code = ref(['', '', '', '', '', '']);
                const timer = ref('1:00');
                const timerInterval = ref(null);
                const previousPhoneValue = ref('');
                const fileUploaded = ref(false);
                const fileName = ref('');
                const handleBeforeInput = (event) => {
                    previousPhoneValue.value = event.target.value;
                    
                    if (event.inputType === 'deleteContentBackward') {
                        return true;
                    }
                    
                    if (!/\d/.test(event.data)) {
                        event.preventDefault();
                        return false;
                    }
                    
                    return true;
                };

                const formatPhone = (event) => {
                    const input = event.target;
                    let value = input.value.replace(/\D/g, '');
                    const cursorPosition = input.selectionStart;
                    
                    // Обработка backspace
                    if (event.inputType === 'deleteContentBackward' && previousPhoneValue.value.length > input.value.length) {
                        // Находим позицию удаляемого символа
                        const diffPos = findDiffPos(previousPhoneValue.value, input.value);
                        if (diffPos !== -1 && /\D/.test(previousPhoneValue.value[diffPos])) {
                            // Если удаляем разделитель, удаляем и предыдущую цифру
                            const newValue = input.value.substring(0, diffPos - 1) + input.value.substring(diffPos);
                            input.value = newValue;
                            value = newValue.replace(/\D/g, '');
                            input.setSelectionRange(diffPos - 1, diffPos - 1);
                            formData.value.phone = applyPhoneMask(value);
                            return;
                        }
                    }

                    // Ограничение длины
                    value = value.substring(0, 11);
                    
                    // Применение маски
                    const formattedValue = applyPhoneMask(value);
                    formData.value.phone = formattedValue;
                    input.value = formattedValue;
                    
                    // Корректировка позиции курсора
                    let newCursorPos = cursorPosition;
                    if (event.inputType === 'insertText' || event.inputType === 'insertFromPaste') {
                        newCursorPos += formattedValue.length - previousPhoneValue.value.length;
                    }
                    input.setSelectionRange(newCursorPos, newCursorPos);
                };

                const applyPhoneMask = (phoneDigits) => {
                    if (!phoneDigits) return '';
                    
                    let formatted = '+7 (';
                    if (phoneDigits.length > 1) formatted += phoneDigits.substring(1, 4);
                    if (phoneDigits.length >= 4) formatted += ') ';
                    if (phoneDigits.length > 4) formatted += phoneDigits.substring(4, 7);
                    if (phoneDigits.length >= 7) formatted += '-';
                    if (phoneDigits.length > 7) formatted += phoneDigits.substring(7, 9);
                    if (phoneDigits.length >= 9) formatted += '-';
                    if (phoneDigits.length > 9) formatted += phoneDigits.substring(9, 11);
                    
                    return formatted;
                };

                const findDiffPos = (str1, str2) => {
                    const len = Math.min(str1.length, str2.length);
                    for (let i = 0; i < len; i++) {
                        if (str1[i] !== str2[i]) return i;
                    }
                    return str1.length !== str2.length ? len : -1;
                };

                const phoneKeyDown = (event) => {
                    // Разрешаем: backspace, delete, tab, escape, enter, стрелки
                    if ([8, 46, 9, 27, 13, 37, 38, 39, 40].includes(event.keyCode) || 
                        (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode))) {
                        return;
                    }
                    
                    // Запрещаем нецифровые символы
                    if ((event.keyCode < 48 || event.keyCode > 57) && 
                        (event.keyCode < 96 || event.keyCode > 105)) {
                        event.preventDefault();
                    }
                };

                const handleFileUpload = (event) => {
                    if (event.target.files && event.target.files[0]) {
                        formData.value.educationCertificate = event.target.files[0];
                        fileUploaded.value = true;
                        fileName.value = event.target.files[0].name; // сохраняем имя файла
                    }
                };

                const selectPosition = (option) => {
                    formData.value.position = option;
                };

                const selectCity = (option) => {
                    formData.value.city = option;
                };

                const validateForm = () => {
                    errors.value = {};
                    let isValid = true;

                    if (!formData.value.lastName.trim()) {
                        errors.value.lastName = 'Пожалуйста, введите фамилию';
                        isValid = false;
                    }
                    
                    if (!formData.value.firstName.trim()) {
                        errors.value.firstName = 'Пожалуйста, введите имя';
                        isValid = false;
                    }
                    
                    const phoneDigits = formData.value.phone.replace(/\D/g, '');
                    if (!phoneDigits) {
                        errors.value.phone = 'Пожалуйста, введите телефон';
                        isValid = false;
                    } else if (phoneDigits.length !== 11) {
                        errors.value.phone = 'Номер телефона должен содержать 11 цифр';
                        isValid = false;
                    }
                    
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!formData.value.email) {
                        errors.value.email = 'Пожалуйста, введите email';
                        isValid = false;
                    } else if (!emailRegex.test(formData.value.email)) {
                        errors.value.email = 'Пожалуйста, введите корректный email';
                        isValid = false;
                    }
                    
                    if (!formData.value.position) {
                        errors.value.position = 'Пожалуйста, выберите должность';
                        isValid = false;
                    }
                    
                    if (!formData.value.city) {
                        errors.value.city = 'Пожалуйста, выберите город';
                        isValid = false;
                    }
                    
                    if (!formData.value.privacyPolicy) {
                        errors.value.privacyPolicy = 'Необходимо согласие с политикой конфиденциальности';
                        isValid = false;
                    }

                    return isValid;
                };

                const submitForm = () => {
                    if (!validateForm()) {
                        return;
                    }

                    const phoneDigits = formData.value.phone.replace(/\D/g, '');
                    const formDataToSend = new FormData();
                    formDataToSend.append('lastName', formData.value.lastName);
                    formDataToSend.append('firstName', formData.value.firstName);
                    formDataToSend.append('phone', formData.value.phone);
                    formDataToSend.append('email', formData.value.email);
                    formDataToSend.append('position', formData.value.position);
                    formDataToSend.append('city', formData.value.city);
                    formDataToSend.append('privacyPolicy', formData.value.privacyPolicy);
                    formDataToSend.append('newsletter', formData.value.newsletter);

                    if (formData.value.educationCertificate instanceof File) {
                        formDataToSend.append('educationCertificate', formData.value.educationCertificate);
                    }

                    const componentName = 'frog:registration';
                    const action = 'sendCode';
                    const url = `/bitrix/services/main/ajax.php?c=${componentName}&action=${action}&mode=class`;

                    fetch(url, {
                        method: 'POST',
                        body: formDataToSend
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showCodeVerification.value = true;
                            startTimer();
                        } else {
                            showMessage(data.message || 'Ошибка при отправке кода', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessage('Ошибка сети', 'error');
                    });
                };

                const verifyCode = () => {
                    const fullCode = code.value.join('');
                    if (fullCode.length !== 6) {
                        showMessage('Пожалуйста, введите полный код', 'error');
                        return;
                    }

                    const phoneDigits = formData.value.phone.replace(/\D/g, '');
                    const formDataToSend = new FormData();
                    formDataToSend.append('lastName', formData.value.lastName);
                    formDataToSend.append('firstName', formData.value.firstName);
                    formDataToSend.append('phone', formData.value.phone);
                    formDataToSend.append('email', formData.value.email);
                    formDataToSend.append('position', formData.value.position);
                    formDataToSend.append('city', formData.value.city);
                    formDataToSend.append('privacyPolicy', formData.value.privacyPolicy);
                    formDataToSend.append('newsletter', formData.value.newsletter);
                    formDataToSend.append('code', fullCode);

                    if (formData.value.educationCertificate instanceof File) {
                        formDataToSend.append('educationCertificate', formData.value.educationCertificate);
                    }

                    const componentName = 'frog:registration';
                    const action = 'register';
                    const url = `/bitrix/services/main/ajax.php?c=${componentName}&action=${action}&mode=class`;

                    fetch(url, {
                        method: 'POST',
                        body: formDataToSend
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Показываем уведомление вместо редиректа
                                BX.UI.Notification.Center.notify({
                                    content: "Регистрация успешно пройдена. Пароль отправлен на вашу почту.",
                                    autoHideDelay: 5000, // автоматически скрыть через 5 секунд
                                    position: 'top-right',
                                    closeBtn: true, // показывать кнопку закрытия
                                    type: 'success' // тип уведомления (success, info, warning, danger)
                                });

                                // Если нужно сделать редирект после закрытия уведомления
                                if (data.data.redirect) {
                                    setTimeout(() => {
                                        window.location.href = data.data.redirect;
                                    }, 5000);
                                }
                            } else {
                                showMessage(data.message || 'Ошибка при регистрации', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showMessage('Ошибка сети', 'error');
                        });
                };

                const focusNext = (index) => {
                    if (index < 6 && code.value[index - 1] !== '') {
                        document.querySelectorAll('.code-box')[index].focus();
                    }
                };

                const startTimer = () => {
                    let timeLeft = 60;
                    timerInterval.value = setInterval(() => {
                        timeLeft--;
                        timer.value = `0:${timeLeft < 10 ? '0' : ''}${timeLeft}`;
                        if (timeLeft <= 0) {
                            clearInterval(timerInterval.value);
                        }
                    }, 1000);
                };

                const showMessage = (msg, type) => {
                    message.value = msg;
                    messageClass.value = type;
                    setTimeout(() => {
                        message.value = '';
                        messageClass.value = '';
                    }, 5000);
                };

                onMounted(() => {
                    // Инициализация при загрузке
                });

                return {
                    formData,
                    positions,
                    cities,
                    errors,
                    message,
                    messageClass,
                    showCodeVerification,
                    code,
                    timer,
                    handleBeforeInput,
                    formatPhone,
                    phoneKeyDown,
                    handleFileUpload,
                    selectPosition,
                    selectCity,
                    submitForm,
                    verifyCode,
                    focusNext,
                    showMessage,
                    fileUploaded,
                    fileName,
                };
            }
        });

        app.mount('#registration-app');
    });
</script>