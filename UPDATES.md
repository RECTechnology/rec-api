How to introduce updates in the API properly
============================================

## Code updates

Simply push the new code to the `master branch` to see it in the **staging**
installation and to `stable branch` to see it in **production**

## Database updates
Database updates (schema updates) are a little bit more complicated than simply
updating the code, so we have to deal with the existing production data.

In order to resolve this, there is a list of things to take into account

### Adding new fields to entities
#### Deploy compatible code
create new fields as a functions with `@Serializer\VirtualProperty` annotations.
This will return the values but it wont require any field to be present in the
database yet (push 1).
```php
/**
 * @Serializer\VirtualProperty()
 * @Serializer\SerializedName("status")
 * @Serializer\Type("string")
 */
public function getStatus(): string
{
    return self::STATUS_CREATED;
}
```
Note that this step can be avoided if you don't need the fields yet.

#### Execute migrations
At this point, we will execute the migration, and the new fields will be available
in the tables, but the code wont be interacting with it yet.

#### Deploy new code
Now we can add the `@ORM\Column()` and field to the entity and deploy the safely (push 2).
```php
/**
 * @ORM\Column(type="string")
 */
private $status;

public function setStatus($status): void
{
    return $this->status = $status;
}

public function getStatus(): string
{
    return $this->status;
}
```

### Removing fields from entities
Removing fields from entities will be done at the inverse, first deploy the code
without the useless fields and then apply the migration (single push).