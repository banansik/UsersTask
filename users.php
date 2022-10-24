<?
/*
 * W tej funkcji za dużo się dzieję, iterujemy po uzytkownikach tabeli, walidujemy dane i potem wysyłamy je do bazy danych.
 * Zgodnie z zasadą pojedyńczej odpowiedzialności, podzieliłbym tą funkcję na kilka mniejszych.
 *
 */
public function updateUsers($users) // ze względu na czytelność kodu i bezpieczeństwo uzyłbym tutaj typehintów. Skoro chcemu iterować po tabeli User to dodałbym tutaj typehint array bo jeśli zostanie przkazany inny parametr to i tak zostanie wyrzucony błąd. Skoro return zwraca metodę redirect to również powinnismy to uwzględnić jak return hint.
{
    /**
     * starałbym się unikać wykonywania zapytań do bazy danych w pętli,
     * czy nie lepiej byłoby przeprowadzić walidację, zebrac dane do nowej tabeli i wrzucić je za pomocą jednego zapytania?
     *
     * Jeśli funkcja przyjmuje jakiś bardziej złożony parametr, dobrze byłoby określić jego strukturę. Ułatwi to potem analize tego kodu.
     */
	foreach ($users as $user) {
		try {
            /**
             * czy poniższa walidacja jest wystarczająca?Czy nie powinniśmy określić minimalnej długości hasła, formatu e-mail itd.?
             */
			if ($user['name'] && $user['login'] && $user['email'] && $user['password'] && strlen($user['name']) >= 10) //to powinno być wydzielone do nowej funkcji, kod powinnien być czytelny a tutaj musimy zagłebiać się w logikę ifa.
				DB::table('users')->where('id', $user['id'])->update([
					'name' => $user['name'],
					'login' => $user['login'],
					'email' => $user['email'],
					'password' => md5($user['password']) // do rozważenia czy algorytm zapewnia wystarczające bezpieczeństwo
				]);
		} catch (\Throwable $e) {
			return Redirect::back()->withErrors(['error', ['We couldn\'t update user: ' . $e->getMessage()]]); //czy przekazywanie wiadomości błędu bezpośrednio do użytkownika jest bezpieczne? Warto byłoby zastanowić się jakie problemy mogą wystąpić i stworzyć odpowiednią obsługę tych błędów.
		}
	}
	return Redirect::back()->with(['success', 'All users updated.']);
}
/*
 * ta sama uwaga, dotycząca zasady pojedyńczej odpowiedzialności, funkcja waliduje, wysyła zapytania do bazy i wiadomości e-mail. Podzieliłbym to na osobne funkcje.
 */
public function storeUsers($users)//typehints
{

    foreach ($users as $user) {
        try {
            //czy poniższa walidacja jest wystarczająca? Co w wypadku gdy nazwa użytkownika już istnieje?
			if ($user['name'] && $user['login'] && $user['email'] && $user['password'] && strlen($user['name']) >= 10)//zdublowany kod, ta sama walidacja znajduję się w funkcji updateUsers
				DB::table('users')->insert([
					'name' => $user['name'],
					'login' => $user['login'],
					'email' => $user['email'],
					'password' => md5($user['password'])
            ]);
        } catch (\Throwable $e) {
            return Redirect::back()->withErrors(['error', ['We couldn\'t store user: ' . $e->getMessage()]]);
        }
    }
    //skoro to nie jest klasa to do czego się tutaj odwołujemy?
    $this->sendEmail($users); //czy ta funkcja znajduje się w dobrym miejscu? Czy nie lepiej byłoby wysłać wiadomość pojedyńczo do każdego użytkownika gdy konto zostanie utworozne poprawnie?
    return Redirect::back()->with(['success', 'All users created.']);
}

/*
 * happy path, co w przypadku gdy coś pójdzie źle? Należy rozważyć możliwe błędy i je obsłużyć.
 */
private function sendEmail($users)//typehints
{
    foreach ($users as $user) {
        $message = 'Account has beed created. You can log in as <b>' . $user['login'] . '</b>';
        if ($user['email']) {
            Mail::to($user['email'])
                ->cc('support@company.com')
                ->subject('New account created')
                ->queue($message);
        }
    }
    return true;
}

?>