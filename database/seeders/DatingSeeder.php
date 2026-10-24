<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatingSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['Алиса', 'female', '2002-06-14', 'alice', 'Отношения', 'Собираю маленькие радости: утренний кофе, билеты в один конец и людей, с которыми можно быть собой. Пойдём гулять без маршрута?', ['Путешествия', 'Кофе', 'Фотография', 'Музыка']],
            ['София', 'female', '2001-03-07', 'sofia', 'Дружба', 'Дизайнер, обожаю кино не по плану и долгие завтраки. Ищу своего человека для больших и маленьких приключений.', ['Кино', 'Искусство', 'Кофе']],
            ['Марк', 'male', '1999-11-23', 'mark', 'Отношения', 'Играю на гитаре, ищу лучшие горы и умею готовить пасту. Расскажи, куда ты мечтаешь уехать?', ['Музыка', 'Горы', 'Путешествия']],
            ['Анна', 'female', '2000-08-20', 'anna', 'Общение', 'Живу между книжным магазином и студией керамики. Буду рада человеку с тёплым чувством юмора.', ['Книги', 'Искусство', 'Прогулки']],
            ['Даниил', 'male', '1998-05-09', 'daniel', 'Дружба', 'Бегаю по утрам, работаю с музыкой. Люблю людей, которым интересно жить.', ['Спорт', 'Музыка', 'Кино']],
            ['Лера', 'female', '2003-02-11', 'lera', 'Отношения', 'Можно сразу к интересному: какой момент этой недели ты хотел бы повторить?', ['Кофе', 'Книги', 'Путешествия']],
        ];
        foreach ($people as [$name, $gender, $birthday, $image, $goal, $bio, $interests]) {
            $user = User::firstOrCreate(['email' => $image.'@example.test'], ['name' => $name, 'password' => Hash::make(bin2hex(random_bytes(24)))]);
            $photo = '/images/'.$image.'-portrait.png';
            Profile::updateOrCreate(['user_id' => $user->id], ['birthday' => $birthday, 'gender' => $gender, 'city' => 'Москва', 'goal' => $goal, 'bio' => $bio, 'interests' => $interests, 'photos' => [$photo], 'demo' => true]);
        }
    }
}
