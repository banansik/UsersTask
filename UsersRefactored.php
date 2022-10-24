<?php
declare(strict_types=1);

class UsersRefactored
{
    private const USER_TABLE_NAME = 'users'; // ze względów bezpiczeństa, spóəności i wygody analizy kodu lepiej użyć tutaj consta

    /**
     * @param $users
     *(
     *  należy sprawdzić i określić jakie dane przyjmuje nasza funkcja.
     * [user] => [
     *      [id] => int,
     *      [name] => string,
     *      [login] => string,
     *      [password] => string,
     *      [...]
     * ]
     * )
     * @return mixed
     */
    public function updateUsers(array $users): Redirect
    {
        foreach ($users as $user) {
            try {
                $this->validateUser($user);
                $this->sendUpdateQuery($user);
            } catch (\Throwable $e) {
                return Redirect::back()->withErrors(['error', ['We couldn\'t update user: ' . $e->getMessage()]]); // po utworzeniu systemu wujątków, moglibyśmy stworzyć nalezytą obsługę błędów i logger który przesyłałby informacje o błędach dla administratra.
            }
        }
        return Redirect::back()->with(['success', 'All users updated.']);
    }

    /**
     * @param array $users
     * @return Redirect
     */
    public function storeUsers(array $users): Redirect
    {
        try {
            $this->createUsers($users);
            $this->sendEmail($users);
        } catch (\Throwable $e) {
            return Redirect::back()->withErrors(['error', ['We couldn\'t store user: ' . $e->getMessage()]]);
        }
        return Redirect::back()->with(['success', 'All users created.']);
    }

    /**
     * @param array $users
     */
    private function sendInsertQuery(array $users): void
    {
        try {
            DB::table(self::USER_TABLE_NAME)->insert($users);
        } catch (Exception $e) {
            throw new Exception('Error during inserting data:' . $e->getMessage());
        }
    }

    /**
     * @param array $users
     * @return bool
     * @throws Exception
     */
    private function sendEmail(array $users): void
    {
        foreach ($users as $user) {
            if (!$user['email']) {

                throw new Exception('Email missing exception');
            }
            $message = $this->createEmailMessage($user['login']);

            try {
                Mail::to($user['email'])
                    ->cc('support@company.com')
                    ->subject('New account created')
                    ->queue($message);
            } catch (Exception $e) {
                throw new Exception('Cannot send email.'); // Należałoby utworzyć własne exception aby móc je potem odpowiednio obsłużyć.
            }
        }
    }

    /**
     * @param array $users
     * @throws Exception
     */
    private function createUsers(array $users): void
    {
        $validatedUsers = [];

        foreach ($users as $user) {
            $validatedUsers[] = $this->validateUser($user);
        }

        try {
            $this->sendInsertQuery($validatedUsers);
            $this->sendEmail($validatedUsers);
        } catch (Exception $e) {
            throw new Exception('Cannot create account.');
        }
    }

    /**
     * @param $login
     * @return string
     */
    private function createEmailMessage($login): string
    {
        return 'Account has beed created. You can log in as <b>' . $login . '</b>';
    }

    /**
     * @param array $user
     * @return array|Exception
     */
    private function validateUser(array $user): array|Exception
    {
        /*
         * walidacja powinna być bardziej wymagająca i zwracać odpowiednie informacje w zależności od znalezionego błędu.
         */
        if (!$user['name'] && !$user['login'] && !$user['email'] && !$user['password'] && strlen($user['name']) < 10) {
            return new Exception('Vaidation error');
        }

        return $user;
    }

    /**
     * @param $user
     */
    private function sendUpdateQuery(array $user): void
    {
        DB::table(self::USER_TABLE_NAME)->where('id', $user['id'])->update([
            'name' => $user['name'],
            'login' => $user['login'],
            'email' => $user['email'],
            'password' => md5($user)
        ]);
    }

}

?>