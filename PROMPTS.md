Создай миграцию и модель для лары Request которая будет содержать поля id 
`clientName` (обязательно) стринг максимум 18 символов
`phone` (обязательно) стринг максимум 12 симовлов 
`address` (обязательно) максимум 40 символов 
`problemText` (обязательно) максимум 255 символов 
`status` (одно из): `new | assigned | in_progress | done | canceled` здесь используй enum чтобы ограничить значения 
`assignedTo` (мастер, может быть пустым) используем это поле как внешний ключ цепляясь за модель и миграцию users, сделай ключ и связь к таблице один ко многим от мастера к Request 
`createdAt`, `updatedAt`
18.02.2026 19:49 


    protected $fillable = [
        'clientName',
        'phone',
        'address',
        'problemText',
        'status',
        'assignedTo',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
    ];

    /**
     * Связь: заявка принадлежит мастеру
     */
    public function master()
    {
        return $this->belongsTo(User::class, 'assignedTo');
    }

Для модели создай mvc vue 3 для просмотра информации истории изменений 
18.02.2026 22:49


Создай сиды для 6 юзеров и 10 заявок, учитывай, что при статусе canceled, new атрибут assignedTo
 Должен быть null
19.02.2026 08:45
Создай тесты unit 
cannot take request with invalid status                                                           
   master cannot take others request                                                                 
  master can take own request                                                                       
   race condition when taking request        
19.02.2026 14:37

Обоснуй выбор Laravel и vue 3 tailwind  с точки зрения создания crud приложения
19.02.2026 16:29

