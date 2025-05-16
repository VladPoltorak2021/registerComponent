<?php
declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;
use Local\Orm\RegistrationCodeTable;
use Bitrix\Main\Engine\ActionFilter\Post;

class FrogRegistrationComponent extends CBitrixComponent implements Controllerable
{


    private const DEFAULT_PASSWORD_LENGTH = 10;
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 10;

    public function configureActions(): array
    {
        return [
            'sendCode' => [
                'prefilters' => [],
                'postfilters' => [],
            ],
            'register' => [
                'prefilters' => [],
                'postfilters' => [],
            ],
        ];
    }

    public function executeComponent(): void
    {

        $this->includeComponentTemplate();
    }

    public function sendCodeAction(): AjaxJson
    {
        try {
            $formData = $_POST;
            $this->validateEmail($formData['email']);
            $this->validatePhone($formData['phone']);

            // Обработка файла
            if (isset($formData['educationCertificate'])) {
                $file = $formData['educationCertificate'];
                $filePath = $file['tmp_name'];
                // Сохранение файла
            }

            // Генерация и отправка кода
            $code = $this->generateAndSaveCode($formData['email']);
            $this->sendCodeToEmail($formData['email'], $code);

            return AjaxJson::createSuccess([
                'message' => 'Код подтверждения отправлен на ваш email.',
            ]);
        } catch (\Exception $e) {
            // Создаем ErrorCollection и добавляем ошибку
            $errorCollection = new ErrorCollection();
            $errorCollection[] = new Error($e->getMessage());

            // Возвращаем ошибку
            return AjaxJson::createError($errorCollection);
        }
    }

    public function registerAction(): AjaxJson
    {
        try {
            $formData = $_POST;
            $this->validateFormData($formData);
            $this->validateCode($formData['email'], $formData['code']);

            // Создаём пользователя и получаем ID + пароль
            $userData = $this->createUser($formData);
            $userId = $userData['id'];
            $generatedPassword = $userData['password'];

            $this->saveAdditionalFields($userId, $formData);
            $this->deleteCode($formData['email']);

            // Авторизуем пользователя
            global $USER;
            if (!$USER->IsAuthorized()) {
                $USER->Authorize($userId);
            }

            $eventFields = [
                "EMAIL" => $formData['email'],
                "LOGIN" => $formData['email'],
                "PASSWORD" => $generatedPassword,
                "USER_ID" => $userId,
            ];

            \CEvent::Send("SET_INFO_REGIGSTRATION", SITE_ID, $eventFields);

            return AjaxJson::createSuccess([
                'message' => 'Регистрация успешно завершена! На ваш email отправлены данные для входа.',
                'userId' => $userId,
                'redirect' => '/',
            ]);

        } catch (\Exception $e) {
            $errorCollection = new ErrorCollection();
            $errorCollection[] = new Error($e->getMessage());
            return AjaxJson::createError($errorCollection);
        }
    }


    private function validateEmail(string $email): void
    {
        if (!check_email($email)) {
            throw new \Exception('Некорректный формат email');
        }

        if (UserTable::getRow(['filter' => ['=EMAIL' => $email]])) {
            throw new \Exception('Пользователь с таким email уже существует');
        }
    }

    private function validateCode(string $email, string $code): void
    {
        $currentTime = new \Bitrix\Main\Type\DateTime();

        $result = \Local\Orm\RegistrationCodeTable::getList([
            'filter' => [
                '=EMAIL' => $email,
                '=CODE' => $code,
                '>EXPIRES_AT' => $currentTime,
            ],
            'limit' => 1,
        ]);

        if (!$result->fetch()) {
            throw new \Exception('Неверный или просроченный код подтверждения');
        }
    }

    private function generateCode(): string
    {
        return (string) rand(100000, 999999); // Преобразуем int в string
    }

    private function generateAndSaveCode(string $email): string
    {
        $code = $this->generateCode();
        $expiresAt = (new \Bitrix\Main\Type\DateTime())->add('PT10M'); // Код действителен 10 минут

        \Local\Orm\RegistrationCodeTable::add([
            'EMAIL' => $email,
            'CODE' => $code,
            'EXPIRES_AT' => $expiresAt,
        ]);

        return $code;
    }

    private function saveCode(string $email, string $code): void
    {
        $expiresAt = (new DateTime())->add(sprintf('PT%dM', self::CODE_EXPIRY_MINUTES));

        RegistrationCodeTable::add([
            'EMAIL' => $email,
            'CODE' => $code,
            'EXPIRES_AT' => $expiresAt,
        ]);
    }

    private function deleteCode(string $email): void
    {
        \Local\Orm\RegistrationCodeTable::deleteByFilter(['=EMAIL' => $email]);
    }

    private function sendCodeToEmail(string $email, string $code): void
    {
        \CEvent::Send('REGISTRATION_CONFIRMATION_CODE', SITE_ID, [
            'EMAIL' => $email,
            'CODE' => $code,
        ]);
    }

    private function validateFormData(array $data): void
    {
        $requiredFields = [
            'lastName', 'firstName', 'email',
            'phone', 'position', 'city',
            'privacyPolicy', 'code'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Поле {$field} обязательно для заполнения");
            }
        }
    }

    private function createUser(array $data): array
    {
        $user = new \CUser;
        $password = $this->generatePassword();

        $userId = $user->Add([
            'NAME' => $data['firstName'],
            'LAST_NAME' => $data['lastName'],
            'EMAIL' => $data['email'],
            'LOGIN' => $data['email'],
            'PERSONAL_PHONE' => $data['phone'],
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
        ]);

        if (!$userId) {
            throw new \Exception($user->LAST_ERROR);
        }

        return [
            'id' => $userId,
            'password' => $password, // Возвращаем пароль
        ];
    }

    private function saveAdditionalFields(int $userId, array $data): void
    {
        AddMessage2Log('file = '.print_r($data, true),'');
        $user = new \CUser;
        $user->Update($userId, [
            'WORK_POSITION' => $data['position'],
            'PERSONAL_CITY' => $data['city'],
            'UF_NEWSLETTER' => $data['newsletter'] ?? 'N',
            'UF_EDUCATION_FILE' => $this->saveEducationFile($_FILES['educationCertificate']),
        ]);
    }

    private function saveEducationFile(array $file): array
    {
        return \CFile::MakeFileArray($file['tmp_name'], $file['type']);
    }

    private function generatePassword(): string
    {
        return \Bitrix\Main\Security\Random::getString(
            self::DEFAULT_PASSWORD_LENGTH,
            true,
            true
        );
    }

    private function validatePhone(string $phone): void
    {
        if (!preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
            throw new \Exception('Некорректный формат телефона. Ожидается формат: +7 (XXX) XXX-XX-XX');
        }
    }

}